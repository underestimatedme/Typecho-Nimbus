#!/usr/bin/env bash
# 渲染冒烟测试：启动 php -S，请求关键页面并断言。
cd "$(dirname "$0")/.."
SITE=".dev/typecho"; PORT=8099; BASE="http://localhost:$PORT"
[ -f "$SITE/config.inc.php" ] || { echo "run tests/dev-site.sh first"; exit 1; }
if ! curl -s -o /dev/null "$BASE/" ; then
  (php -d error_reporting=8191 -S 127.0.0.1:$PORT -t "$SITE" tests/router.php >/tmp/nimbus-php.log 2>&1 &)
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
check "/index.php/archives/3/" 200 'class="n-post-hero"' 'data-layout="tri"' 'data-width="fluid"'
check "/index.php/archives/9/" 200 'n-nav-solid' 'class="n-post-head'
check "/index.php/category/tech/" 200 'data-page="index"'
check "/index.php/archives.html" 200 'data-page="page"'
check "/index.php/links.html" 200 'data-page="page"'
check "/index.php/search/test/" 200 'data-page="index"'
check "/index.php/messages.html" 200 'n-comments-wall'
check "/index.php/timeline.html" 200 'data-river'
check "/index.php/music.html" 200 'data-music-page'
check "/index.php/stats.html" 200 'n-ch-heat'
check "/" 200 'id="n-player"'
check "/usr/themes/Nimbus/assets/js/modules/player.js" 200 'export function play'
check "/index.php/archives/1/" 200 'data-share-poster'
check "/usr/themes/Nimbus/assets/js/lib/qr.js" 200 'export function qrMatrix'
check "/index.php/not-exist-page/" 404 'data-page="error"'
# 短代码与回复可见（示例文章由 tests/seed-kit.php 创建）
SC=$(curl -s "$BASE/index.php/search/%E7%9F%AD%E4%BB%A3%E7%A0%81%E6%BC%94%E7%A4%BA/" | grep -o 'href="[^"]*archives/[0-9]*/"' | head -1 | sed 's/href="//;s/"$//')
if [ -n "$SC" ]; then
  body=$(curl -s "$SC")
  echo "$body" | grep -q 'n-sc-tip is-warning' || { echo "FAIL shortcode render"; fail=1; }
  echo "$body" | grep -q 'nimbus2026' && { echo "FAIL hidden content leaked to anonymous visitor"; fail=1; }
  curl -s "$BASE/index.php/feed/" | grep -q 'nimbus2026' && { echo "FAIL hidden content leaked to RSS"; fail=1; }
fi
# NimbusKit（插件启用时）
if curl -s "$BASE/" | grep -q '"api":"http'; then
  O="Origin: $BASE"; API="$BASE/index.php/nimbus-api"
  r=$(curl -s -H "$O" -d 'cid=3' "$API/like"); echo "$r" | grep -q '"ok":true' || { echo "FAIL like: $r"; fail=1; }
  c=$(curl -s -o /dev/null -w '%{http_code}' -H 'Origin: http://evil.example' -d 'cid=3' "$API/like"); [ "$c" = "403" ] || { echo "FAIL cross-origin like: $c"; fail=1; }
  r=$(curl -s "$API/search?q=Nimbus"); echo "$r" | grep -q '"ok":true' || { echo "FAIL search: $r"; fail=1; }
  c=$(curl -s -o /tmp/nimbus-sitemap.xml -w '%{http_code}' "$BASE/index.php/sitemap.xml"); { [ "$c" = "200" ] && grep -q '<urlset' /tmp/nimbus-sitemap.xml; } || { echo "FAIL sitemap: $c"; fail=1; }
  check "/index.php/moments.html" 200 'class="n-moments"'
  check "/index.php/albums.html" 200 'class="n-albums"'
  check "/index.php/albums.html?album=1" 200 'class="n-photos"'
  check "/index.php/bookmarks.html" 200 'data-bookmarks' 'class="n-bm n-reveal"'
  check "/index.php/projects.html" 200 'class="n-projects"'
fi
[ $fail -eq 0 ] && echo "smoke ok"
exit $fail
