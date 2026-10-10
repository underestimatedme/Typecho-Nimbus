#!/usr/bin/env bash
# GitHub 登录评论端到端测试（假 GitHub 服务器）。需测试站正在运行：bash tests/serve.sh
set -u
cd "$(dirname "$0")/.."
BASE="${BASE:-http://localhost:8099}"; DB=.dev/typecho/usr/nimbus.db; GH=8098
POST="$BASE/index.php/archives/1/"
fail=0; ok() { echo "  ok   $1"; }; bad() { echo "  FAIL $1"; fail=1; }
python3 tests/fake-github.py $GH & GHPID=$!
OLDCFG=$(sqlite3 "$DB" "select value from typecho_options where name='plugin:NimbusKit'")
OLDINT=$(sqlite3 "$DB" "select value from typecho_options where name='commentsPostIntervalEnable'")
cleanup() {
  kill $GHPID 2>/dev/null
  sqlite3 "$DB" "update typecho_options set value='$(printf %s "$OLDCFG" | sed "s/'/''/g")' where name='plugin:NimbusKit'"
  sqlite3 "$DB" "update typecho_options set value='$OLDINT' where name='commentsPostIntervalEnable'"
  rm -f "$J1" "$J2" "$J3" /tmp/nimbus-gh-*.txt
}
J1=$(mktemp); J2=$(mktemp); J3=$(mktemp); trap cleanup EXIT
sqlite3 "$DB" "update typecho_options set value='0' where name='commentsPostIntervalEnable'"
php tests/kit-config.php "$DB" githubClientId=test-client githubClientSecret=test-secret githubOnly=0 githubBase=http://127.0.0.1:$GH githubApi=http://127.0.0.1:$GH >/dev/null
sleep 0.5

# 1. 未登录：显示登录按钮
curl -s "$POST" | grep -q 'n-gh-bar' && ok "未登录显示 GitHub 登录按钮" || bad "未登录显示 GitHub 登录按钮"

# 2. 完整登录流程，回跳到文章并带上身份 cookie
FINAL=$(curl -s -o /dev/null -w '%{url_effective}' -L -c "$J1" -b "$J1" "$BASE/index.php/nimbus-api/github-login?back=$(printf %s "$POST#comments" | sed 's/#/%23/')")
case "$FINAL" in "$POST"*) ok "登录后回跳到文章";; *) bad "登录后回跳到文章（$FINAL）";; esac
grep -q 'nimbus_gh' "$J1" && ok "写入签名身份 cookie" || bad "写入签名身份 cookie"
PAGE=$(curl -s -b "$J1" "$POST")
echo "$PAGE" | grep -q '@octocat' && ok "表单显示登录身份" || bad "表单显示登录身份"
echo "$PAGE" | grep -q 'The Octocat (Dev)' && ! echo "$PAGE" | grep -q 'The &lt;Octocat' && ok "昵称已清洗" || bad "昵称已清洗"

# 3. 提交评论时篡改昵称邮箱，服务端以 GitHub 身份为准
TOK=$(echo "$PAGE" | grep -o 'name="_" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"$//')
ACTION=$(echo "$PAGE" | grep -o 'action="[^"]*comment[^"]*"' | head -1 | sed 's/action="//;s/"$//')
TEXT="gh-flow-$(date +%s)"
R=$(curl -s -b "$J1" -e "$POST" --data-urlencode "author=Hacker" --data-urlencode "mail=evil@example.com" --data-urlencode "url=https://evil.example.com" --data-urlencode "text=$TEXT" --data-urlencode "_=$TOK" -d nimbus_ajax=1 "$ACTION")
echo "$R" | grep -q '"ok":true' && ok "GitHub 用户评论成功" || bad "GitHub 用户评论成功（$R）"
ROW=$(sqlite3 "$DB" "select author||'|'||mail||'|'||url from typecho_comments where text='$TEXT'")
[ "$ROW" = "The Octocat (Dev)|583231+octocat@users.noreply.github.com|https://github.com/octocat" ] && ok "评论身份不可伪造（含括号的显示名也能评论）" || bad "评论身份不可伪造（$ROW）"

# 4. 开放重定向：外站 back 回到首页
LOC=$(curl -s -o /dev/null -w '%{redirect_url}' "$BASE/index.php/nimbus-api/github-login?back=https://evil.com/")
STATE=$(printf %s "$LOC" | sed -n 's/.*state=\([^&]*\).*/\1/p')
FINAL2=$(curl -s -o /dev/null -w '%{url_effective}' -L -c "$J2" -b "$J2" "$BASE/index.php/nimbus-api/github-login?back=https://evil.com/")
case "$FINAL2" in "$BASE"/*) ok "外站回跳地址被替换为本站";; *) bad "外站回跳地址被替换为本站（$FINAL2）";; esac

# 5. 伪造 state / 缺少浏览器随机数（登录 CSRF）
C=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/index.php/nimbus-api/github-callback?code=fake-code&state=forged.sig")
[ "$C" = "400" ] && ok "伪造 state 被拒绝" || bad "伪造 state 被拒绝（$C）"
C=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/index.php/nimbus-api/github-callback?code=fake-code&state=$STATE")
[ "$C" = "400" ] && ok "没有本浏览器随机数的回调被拒绝" || bad "没有本浏览器随机数的回调被拒绝（$C）"

# 6. 伪造身份 cookie 无效
FAKE=$(printf '{"id":1,"login":"root","name":"root","mail":"r@x.com","url":"https://github.com/root","exp":9999999999}' | base64 | tr '+/' '-_' | tr -d '=\n').AAAA
curl -s -b "nimbus_gh=$FAKE" "$POST" | grep -q '@root' && bad "伪造身份 cookie 无效" || ok "伪造身份 cookie 无效"

# 7. 仅 GitHub 用户可评论
php tests/kit-config.php "$DB" githubOnly=1 >/dev/null
P2=$(curl -s -c "$J3" -b "$J3" "$POST")
echo "$P2" | grep -q 'n-gh-gate' && ok "仅 GitHub 模式隐藏匿名表单" || bad "仅 GitHub 模式隐藏匿名表单"
TOK2=$(echo "$P2" | grep -o 'name="_" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"$//')
C=$(curl -s -o /tmp/nimbus-gh-r.txt -w '%{http_code}' -b "$J3" -e "$POST" --data-urlencode "author=Anon" --data-urlencode "mail=anon@example.com" --data-urlencode "text=anon-$TEXT" --data-urlencode "_=$TOK2" -d nimbus_ajax=1 "$ACTION")
[ "$C" = "403" ] && grep -q 'GitHub 登录' /tmp/nimbus-gh-r.txt && ok "匿名评论被拒绝并提示原因" || bad "匿名评论被拒绝并提示原因（$C）"
[ "$(sqlite3 "$DB" "select count(*) from typecho_comments where text='anon-$TEXT'")" = "0" ] && ok "匿名评论未入库" || bad "匿名评论未入库"
R=$(curl -s -b "$J1" -e "$POST" --data-urlencode "author=x" --data-urlencode "mail=x@example.com" --data-urlencode "text=$TEXT-2" --data-urlencode "_=$TOK" -d nimbus_ajax=1 "$ACTION")
echo "$R" | grep -q '"ok":true' && ok "GitHub 用户仍可评论" || bad "GitHub 用户仍可评论（$R）"

# 8. 显示名与本站注册用户重名时加 @login，不能冒充站长
php tests/kit-config.php "$DB" githubOnly=0 >/dev/null
kill $GHPID 2>/dev/null; sleep 0.3
FAKE_GH_NAME=admin python3 tests/fake-github.py $GH & GHPID=$!; sleep 0.5
J4=$(mktemp)
curl -s -o /dev/null -L -c "$J4" -b "$J4" "$BASE/index.php/nimbus-api/github-login?back=/"
P4=$(curl -s -c "$J4" -b "$J4" "$POST"); TOK4=$(echo "$P4" | grep -o 'name="_" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"$//')
R=$(curl -s -b "$J4" -e "$POST" --data-urlencode "author=x" --data-urlencode "mail=x@example.com" --data-urlencode "text=$TEXT-3" --data-urlencode "_=$TOK4" -d nimbus_ajax=1 "$ACTION")
A=$(sqlite3 "$DB" "select author from typecho_comments where text='$TEXT-3'")
[ "$A" = "admin @octocat" ] && ok "与站长重名时改为「admin @octocat」" || bad "与站长重名时改为「admin @octocat」（$A / $R）"
rm -f "$J4"

# 9. 退出
curl -s -o /dev/null -c "$J1" -b "$J1" "$BASE/index.php/nimbus-api/github-logout?back=/"
curl -s -b "$J1" "$POST" | grep -q "n-gh-who" && bad "退出后清除身份" || ok "退出后清除身份"

sqlite3 "$DB" "delete from typecho_comments where text like '%gh-flow-%'"
sqlite3 "$DB" "update typecho_contents set commentsNum=(select count(*) from typecho_comments c where c.cid=typecho_contents.cid and c.status='approved')"
[ $fail -eq 0 ] && echo "github flow ok"
exit $fail
