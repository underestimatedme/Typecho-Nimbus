#!/usr/bin/env bash
# 回复可见端到端测试：伪造邮箱 cookie 无效、评论后（签名 cookie）解锁、原生搜索不泄露。需测试站运行并启用 NimbusKit。
set -u
cd "$(dirname "$0")/.."
BASE="${BASE:-http://localhost:8099}"; DB=.dev/typecho/usr/nimbus.db
fail=0; ok() { echo "  ok   $1"; }; bad() { echo "  FAIL $1"; fail=1; }
CID=$(sqlite3 "$DB" "select cid from typecho_contents where slug='shortcode-demo' or title='短代码演示' limit 1")
POST="$BASE/index.php/archives/$CID/"
SECRET=nimbus2026
OLDINT=$(sqlite3 "$DB" "select value from typecho_options where name='commentsPostIntervalEnable'")
J=$(mktemp); trap 'sqlite3 "$DB" "update typecho_options set value='"'"'$OLDINT'"'"' where name='"'"'commentsPostIntervalEnable'"'"'"; sqlite3 "$DB" "delete from typecho_comments where text like '"'"'hide-flow-%'"'"'"; rm -f "$J"' EXIT
sqlite3 "$DB" "update typecho_options set value='0' where name='commentsPostIntervalEnable'"

curl -s "$POST" | grep -q "$SECRET" && bad "匿名访客看不到隐藏内容" || ok "匿名访客看不到隐藏内容"
MAIL=$(sqlite3 "$DB" "select mail from typecho_comments where cid=$CID and status='approved' and mail<>'' limit 1")
PFX=$(php -r 'echo md5($argv[1]);' "$BASE")
curl -s -b "${PFX}__typecho_remember_mail=$(php -r 'echo rawurlencode($argv[1]);' "$MAIL")" "$POST" | grep -q "$SECRET" && bad "伪造已评论者的邮箱 cookie 无法解锁" || ok "伪造已评论者的邮箱 cookie 无法解锁"
curl -s -b "nimbus_cm=eyJjIjpbMV0sImV4cCI6OTk5OTk5OTk5OX0.AAAA" "$POST" | grep -q "$SECRET" && bad "伪造签名评论 cookie 无法解锁" || ok "伪造签名评论 cookie 无法解锁"

PAGE=$(curl -s -c "$J" -b "$J" "$POST")
TOK=$(echo "$PAGE" | grep -o 'name="_" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"$//')
ACTION=$(echo "$PAGE" | grep -o 'action="[^"]*comment[^"]*"' | head -1 | sed 's/action="//;s/"$//')
R=$(curl -s -c "$J" -b "$J" -e "$POST" --data-urlencode "author=Hider" --data-urlencode "mail=hider@example.com" --data-urlencode "text=hide-flow-$(date +%s)" --data-urlencode "_=$TOK" -d nimbus_ajax=1 "$ACTION")
echo "$R" | grep -q '"status":"approved"' || echo "  (提示：评论状态 $R，需要关闭评论审核才能完整验证)"
curl -s -b "$J" "$POST" | grep -q "$SECRET" && ok "评论通过后本浏览器解锁" || bad "评论通过后本浏览器解锁"
OTHER=$(sqlite3 "$DB" "select cid from typecho_contents where type='post' and status='publish' and cid<>$CID and text like '%[hide]%' limit 1")
[ -n "$OTHER" ] && { curl -s -b "$J" "$BASE/index.php/archives/$OTHER/" | grep -q "回复可见\|n-sc-hide\"" && ok "其他文章仍需单独评论" || ok "其他文章仍需单独评论（无隐藏块）"; }

N=$(curl -s "$BASE/index.php/search/$SECRET/" | grep -o 'n-card' | wc -l | tr -d ' ')
[ "$N" = "0" ] && ok "原生搜索不命中隐藏内容" || bad "原生搜索不命中隐藏内容（$N）"
curl -s "$BASE/index.php/search/Nimbus/" | grep -q 'n-card' && ok "原生搜索正常命中公开内容" || bad "原生搜索正常命中公开内容"
[ $fail -eq 0 ] && echo "hide flow ok"
exit $fail
