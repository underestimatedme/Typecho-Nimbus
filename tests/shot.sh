#!/usr/bin/env bash
# 无头 Chrome 截图：bash tests/shot.sh <输出.png> <URL> [宽] [高]
CHROME="${CHROME:-/Applications/Google Chrome.app/Contents/MacOS/Google Chrome}"
FINAL="$1"; URL="$2"; W="${3:-1440}"; H="${4:-1200}"
PROF=$(mktemp -d)
# 先截到临时文件，成功后再替换，失败时不会删掉原图
OUT="$PROF.png"
( "$CHROME" --headless=new --disable-gpu --hide-scrollbars --window-size=$W,$H --virtual-time-budget=5000 --user-data-dir="$PROF" --screenshot="$OUT" "$URL" >/dev/null 2>&1 & )
for i in $(seq 1 40); do [ -s "$OUT" ] && break; sleep 0.5; done
sleep 0.5; pkill -f "$PROF"; sleep 0.3; rm -rf "$PROF" 2>/dev/null
[ -s "$OUT" ] && mv "$OUT" "$FINAL" && echo "$FINAL"
