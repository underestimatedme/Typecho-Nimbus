#!/usr/bin/env bash
# 幂等创建本地 Typecho 测试站（SQLite），链接主题并灌入测试数据。
set -e
cd "$(dirname "$0")/.."
ROOT="$(pwd)"
SITE="$ROOT/.dev/typecho"
PORT=8099
URL="http://localhost:$PORT"
if [ ! -f "$SITE/index.php" ]; then
  echo "Typecho 不存在，正在下载 1.2.1 ..."
  mkdir -p "$ROOT/.dev"
  curl -sL -o "$ROOT/.dev/typecho.zip" https://github.com/typecho/typecho/releases/download/v1.2.1/typecho.zip
  unzip -qo "$ROOT/.dev/typecho.zip" -d "$SITE"
fi
if [ ! -f "$SITE/config.inc.php" ]; then
  echo "安装 Typecho（CLI 模式）..."
  ( cd "$SITE" && TYPECHO_DB_ADAPTER=Pdo_SQLite TYPECHO_DB_FILE="$SITE/usr/nimbus.db" TYPECHO_DB_PREFIX=typecho_ \
    TYPECHO_SITE_URL="$URL" TYPECHO_USER_NAME=admin TYPECHO_USER_PASSWORD=admin123 TYPECHO_USER_MAIL=admin@example.com \
    php install.php >/dev/null )
  # 开启伪静态与评论默认项由 seed 完成
fi
rm -rf "$SITE/usr/themes/Nimbus"
ln -sfn "$ROOT/Nimbus" "$SITE/usr/themes/Nimbus"
php "$ROOT/tests/seed.php" "$SITE/usr/nimbus.db" "$URL"
php "$ROOT/tests/reset-admin.php" "$SITE/usr/nimbus.db"
# 启用 NimbusKit 并灌入插件示例数据（需要站点在运行）
if ! curl -s -o /dev/null "$URL/"; then (PHP_CLI_SERVER_WORKERS=8 php -d error_reporting=8191 -S 127.0.0.1:$PORT -t "$SITE" "$ROOT/tests/router.php" >/tmp/nimbus-php.log 2>&1 &); sleep 1; STARTED=1; fi
BASE="$URL" bash "$ROOT/tests/activate-kit.sh"
php "$ROOT/tests/seed-kit.php" "$SITE/usr/nimbus.db"
php "$ROOT/tests/seed-media.php" "$SITE"
[ "${STARTED:-0}" = "1" ] && pkill -f "php -d error_reporting=8191 -S 127.0.0.1:$PORT"
echo "dev site: $URL  (admin / admin123)"
