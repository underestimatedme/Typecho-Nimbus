#!/usr/bin/env bash
# 检查三套皮肤各自定义了全部必需变量
cd "$(dirname "$0")/.."
vars="bg fg fg-strong fg-soft muted card line line-hover chip glass accent accent-text accent-soft on-accent accent-grad accent-glow orb-a orb-b title-grad shadow shadow-hover hero-overlay code-bg code-fg selection tok-kw tok-str tok-num tok-cm tok-fn tok-op"
bad=0
for t in sky dusk cyber; do
  f=Nimbus/assets/css/theme-$t.css
  [ -f "$f" ] || { echo "missing $f"; bad=1; continue; }
  for v in $vars; do grep -q -- "--$v:" "$f" || { echo "$f missing --$v"; bad=1; }; done
done
[ $bad -eq 0 ] && echo "css vars ok"
exit $bad
