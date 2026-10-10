#!/usr/bin/env bash
# 启动本地测试站（前台运行，Ctrl+C 停止）。
#   bash tests/serve.sh            启动；如果已经在运行，直接提示地址
#   bash tests/serve.sh --restart  停掉正在运行的测试站后重新启动
#   PORT=8100 bash tests/serve.sh  换端口
cd "$(dirname "$0")/.."
PORT="${PORT:-8099}"
URL="http://localhost:$PORT"
[ -f .dev/typecho/config.inc.php ] || bash tests/dev-site.sh

if lsof -nP -iTCP:"$PORT" -sTCP:LISTEN >/dev/null 2>&1; then
  if [ "$1" = "--restart" ]; then
    pids=$(pgrep -f "php -S 127.0.0.1:$PORT")
    if [ -z "$pids" ]; then echo "端口 $PORT 被其他程序占用，请换端口：PORT=8100 bash tests/serve.sh"; exit 1; fi
    kill $pids; sleep 1
    echo "已停止旧的测试站，正在重新启动…"
  elif curl -s "$URL/" | grep -q 'data-page='; then
    echo "Nimbus 测试站已经在运行：$URL   后台：$URL/admin/  (admin / admin123)"
    echo "如需重启：bash tests/serve.sh --restart"
    exit 0
  else
    echo "端口 $PORT 被其他程序占用，请换端口：PORT=8100 bash tests/serve.sh"
    exit 1
  fi
fi

echo "Nimbus 测试站：$URL   后台：$URL/admin/  (admin / admin123)   按 Ctrl+C 停止"
# Typecho 1.2.1 核心在 PHP 8.4+ 上会刷 Deprecated 提醒；本地只屏蔽这一类，警告和错误照常显示
PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-8}" exec php -d error_reporting=8191 -S "127.0.0.1:$PORT" -t .dev/typecho tests/router.php
