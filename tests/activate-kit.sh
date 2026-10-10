#!/usr/bin/env bash
# 在本地测试站链接并启用 NimbusKit（幂等）。需测试站正在运行。
cd "$(dirname "$0")/.."
ROOT="$(pwd)"; SITE="$ROOT/.dev/typecho"; BASE="${BASE:-http://localhost:8099}"
ln -sfn "$ROOT/NimbusKit" "$SITE/usr/plugins/NimbusKit"
JAR=$(mktemp)
TOK=$(curl -s -c "$JAR" -b "$JAR" "$BASE/admin/login.php" | grep -o 'action/login?_=[a-f0-9]*' | head -1)
curl -s -o /dev/null -c "$JAR" -b "$JAR" -e "$BASE/admin/login.php" -d "name=admin&password=admin123&referer=" "$BASE/index.php/$TOK"
PAGE=$(curl -s -b "$JAR" "$BASE/admin/plugins.php")
if echo "$PAGE" | grep -q 'plugins-edit?deactivate=NimbusKit'; then
  if [ "${1:-}" != "--reactivate" ]; then echo "NimbusKit 已启用"; rm -f "$JAR"; exit 0; fi
  # 重新启用以注册新增的钩子（配置会由插件自动备份并恢复）
  OFF=$(echo "$PAGE" | grep -o 'href="[^"]*plugins-edit?deactivate=NimbusKit[^"]*"' | head -1 | sed 's/href="//;s/"$//;s/&amp;/\&/g')
  curl -s -o /dev/null -b "$JAR" -e "$BASE/admin/plugins.php" "$OFF"
  PAGE=$(curl -s -b "$JAR" "$BASE/admin/plugins.php")
fi
LINK=$(echo "$PAGE" | grep -o 'href="[^"]*plugins-edit?activate=NimbusKit[^"]*"' | head -1 | sed 's/href="//;s/"$//;s/&amp;/\&/g')
[ -n "$LINK" ] || { echo "找不到启用链接"; rm -f "$JAR"; exit 1; }
curl -s -o /dev/null -b "$JAR" -e "$BASE/admin/plugins.php" "$LINK"
curl -s -b "$JAR" "$BASE/admin/plugins.php" | grep -q 'plugins-edit?deactivate=NimbusKit' && echo "NimbusKit 启用成功" || { echo "启用失败"; rm -f "$JAR"; exit 1; }
rm -f "$JAR"
