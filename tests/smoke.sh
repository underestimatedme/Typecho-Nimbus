#!/usr/bin/env bash
# 渲染冒烟测试：启动 php -S，请求关键页面并断言。
cd "$(dirname "$0")/.."
SITE=".dev/typecho"; PORT=8099; BASE="http://localhost:$PORT"
[ -f "$SITE/config.inc.php" ] || { echo "run tests/dev-site.sh first"; exit 1; }
if ! curl -s -o /dev/null "$BASE/" ; then
  (php -S 127.0.0.1:$PORT -t "$SITE" >/tmp/nimbus-php.log 2>&1 &)
  for i in $(seq 1 20); do curl -s -o /dev/null "$BASE/" && break; sleep 0.3; done
fi
fail=0
check() { # path expected_status [grep patterns...]
  local path="$1" want="$2"; shift 2
  local body code
  body=$(curl -s -w '\n%{http_code}' "$BASE$path")
  code=${body##*$'\n'}; body=${body%$'\n'*}
  [ "$code" = "$want" ] || { echo "FAIL $path: status $code != $want"; fail=1; }
  if echo "$body" | grep -qE 'Warning:|Notice:|Fatal error|Deprecated:|Parse error'; then echo "FAIL $path: PHP error in output"; echo "$body" | grep -E 'Warning:|Notice:|Fatal error|Deprecated:|Parse error' | head -3; fail=1; fi
  for pat in "$@"; do echo "$body" | grep -q -- "$pat" || { echo "FAIL $path: missing '$pat'"; fail=1; }; done
}
check "/" 200 'data-page="index"'
check "/index.php/archives/1/" 200 'data-page="post"'
check "/index.php/category/tech/" 200 'data-page="index"'
check "/index.php/archives.html" 200 'data-page="page"'
check "/index.php/links.html" 200 'data-page="page"'
check "/index.php/search/test/" 200 'data-page="index"'
check "/index.php/not-exist-page/" 404 'data-page="error"'
[ $fail -eq 0 ] && echo "smoke ok"
exit $fail
