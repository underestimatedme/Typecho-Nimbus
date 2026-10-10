#!/usr/bin/env bash
# 启动本地测试站（前台运行，Ctrl+C 停止）。首次请先运行 tests/dev-site.sh。
cd "$(dirname "$0")/.."
PORT="${PORT:-8099}"
[ -f .dev/typecho/config.inc.php ] || bash tests/dev-site.sh
echo "Nimbus 测试站: http://localhost:$PORT   后台: http://localhost:$PORT/admin/  (admin / admin123)"
exec php -S "127.0.0.1:$PORT" -t .dev/typecho tests/router.php
