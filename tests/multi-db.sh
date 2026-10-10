#!/usr/bin/env bash
# 在临时 MySQL 与 PostgreSQL 实例上安装 Typecho + Nimbus + NimbusKit 并跑接口与页面检查。
# 需要本机有 mysqld/mysql 与 PostgreSQL 的 initdb/pg_ctl/psql（Homebrew：brew install mysql postgresql@17）。
# 用法：bash tests/multi-db.sh [mysql|pgsql|all]   完成后自动停止并删除临时实例。
set -u
cd "$(dirname "$0")/.."
ROOT="$(pwd)"; W="$ROOT/.dev/multidb"; WHICH="${1:-all}"
PGBIN="${PGBIN:-$(dirname "$(command -v initdb 2>/dev/null || echo /opt/homebrew/opt/postgresql@17/bin/initdb)")}"
fail=0; pids=()
cleanup() {
  for p in "${pids[@]:-}"; do [ -n "$p" ] && kill "$p" 2>/dev/null; done
  [ -f "$W/mysql.pid" ] && kill "$(cat "$W/mysql.pid")" 2>/dev/null
  [ -d "$W/pg" ] && LC_ALL=C "$PGBIN/pg_ctl" -D "$W/pg" stop -m fast >/dev/null 2>&1
  sleep 1; rm -rf "$W"
}
trap cleanup EXIT
rm -rf "$W"; mkdir -p "$W"

prepare_copy() { # $1 dir
  mkdir -p "$1"
  (cd "$ROOT/.dev/typecho" && tar --exclude='./config.inc.php' --exclude='./usr/*.db' --exclude='./usr/themes/Nimbus' --exclude='./usr/plugins/NimbusKit' -cf - .) | (cd "$1" && tar -xf -)
  ln -sfn "$ROOT/Nimbus" "$1/usr/themes/Nimbus"; ln -sfn "$ROOT/NimbusKit" "$1/usr/plugins/NimbusKit"
}

run_checks() { # $1 port $2 label
  local B="http://localhost:$1" API="http://localhost:$1/index.php/nimbus-api" O="Origin: http://localhost:$1" J; J=$(mktemp)
  ok() { echo "  ok   $2: $1"; }; bad() { echo "  FAIL $2: $1"; fail=1; }
  curl -s -H "$O" -d cid=1 "$API/like" >/dev/null; r=$(curl -s -H "$O" -d cid=1 "$API/like")
  echo "$r" | grep -q '"likes":1' && ok "like dedupe" "$2" || bad "like dedupe $r" "$2"
  curl -s -c "$J" -b "$J" -H "$O" -d cid=1 "$API/view" >/dev/null; r=$(curl -s -c "$J" -b "$J" -H "$O" -d cid=1 "$API/view")
  echo "$r" | grep -q '"counted":false' && ok "view dedupe" "$2" || bad "view dedupe $r" "$2"
  r=$(curl -s -H "$O" -d 'cid=1&type=moment' "$API/like"); echo "$r" | grep -q '"ok":true' && ok "moment like" "$2" || bad "moment like $r" "$2"
  r=$(curl -s "$API/search?q=Typecho"); echo "$r" | grep -q '"ok":true' && ok "search" "$2" || bad "search $r" "$2"
  r=$(curl -s "$API/search?q=typecho"); echo "$r" | grep -q '"url"' && ok "search case-insensitive" "$2" || bad "search case-insensitive $r" "$2"
  c=$(curl -s -o "$W/sm.xml" -w '%{http_code}' "$B/index.php/sitemap.xml"); { [ "$c" = 200 ] && grep -q '<urlset' "$W/sm.xml"; } && ok "sitemap" "$2" || bad "sitemap $c" "$2"
  for u in / archives/1/ moments.html albums.html "albums.html?album=1" links.html bookmarks.html projects.html stats.html stats.html messages.html timeline.html timeline.html search/Typecho/; do
    c=$(curl -s -o "$W/p.html" -w '%{http_code}' "$B/index.php/$u")
    { [ "$c" = 200 ] && ! grep -qE 'Warning:|Fatal error|Notice:|Database Query Error' "$W/p.html"; } && ok "page /$u" "$2" || bad "page /$u ($c)" "$2"
  done
  grep -q 'n-ch-line' <(curl -s "$B/index.php/stats.html") && ok "stats daily views chart" "$2" || bad "stats daily views chart" "$2"
  grep -q 'GitHub' <(curl -s "$B/index.php/bookmarks.html") && grep -q 'n-project' <(curl -s "$B/index.php/projects.html") && ok "bookmarks/projects data" "$2" || bad "bookmarks/projects data" "$2"
  rm -f "$J"
}

seed_sql() { # 示例数据，SQL 通用写法；$1 时间戳
  local t=$1
  cat <<SQL
INSERT INTO typecho_nimbus_moments (content,images,created,status) VALUES ('多数据库测试','["https://picsum.photos/id/1015/400/400"]',$t,'publish');
INSERT INTO typecho_nimbus_links (name,url,avatar,description,sort,status,created) VALUES ('Link','https://example.com','','d',1,'publish',0);
INSERT INTO typecho_nimbus_albums (title,cover,description,photos,sort,created) VALUES ('Album','','','[{"url":"https://picsum.photos/id/1016/400/300","caption":"c"}]',1,0);
INSERT INTO typecho_nimbus_bookmarks (group_name,name,url,icon,description,sort,status,created) VALUES ('开发','GitHub','https://github.com','','代码托管',1,'publish',0);
INSERT INTO typecho_nimbus_projects (name,description,cover,url,repo,tags,sort,status,created) VALUES ('Nimbus','主题','','https://example.com','https://github.com/x/y','PHP, CSS',1,'publish',0);
SQL
}
pages_sql() { # $1 时间戳 $2 order 列引用
  local t=$1 o=$2 a=$3
  for row in "说说|moments|page-moments.php" "相册|albums|page-albums.php" "友链|links|page-links.php" "收藏夹|bookmarks|page-bookmarks.php" "项目|projects|page-projects.php" "统计|stats|page-stats.php" "留言板|messages|page-messages.php" "时间线|timeline|page-timeline.php"; do
    IFS='|' read -r title slug tpl <<< "$row"
    echo "INSERT INTO typecho_contents (title,slug,created,modified,text,$o,$a,template,type,status,parent) VALUES ('$title','$slug',$t,$t,'x',0,1,'$tpl','page','publish',0);"
  done
}

if [ "$WHICH" = all ] || [ "$WHICH" = mysql ]; then
  echo "== MySQL =="
  mysqld --initialize-insecure --datadir="$W/mysql-data" --log-error="$W/mysql-init.log" >/dev/null 2>&1
  (mysqld --datadir="$W/mysql-data" --port=33077 --bind-address=127.0.0.1 --socket="$W/mysql.sock" --mysqlx=OFF --log-error="$W/mysql.log" --pid-file="$W/mysql.pid" >/dev/null 2>&1 &)
  for i in $(seq 1 40); do mysql -h127.0.0.1 -P33077 -uroot -e 'select 1' >/dev/null 2>&1 && break; sleep 1; done
  mysql -h127.0.0.1 -P33077 -uroot -e 'CREATE DATABASE typecho CHARACTER SET utf8mb4'
  prepare_copy "$W/tc-mysql"
  (cd "$W/tc-mysql" && TYPECHO_DB_ADAPTER=Pdo_Mysql TYPECHO_DB_HOST=127.0.0.1 TYPECHO_DB_PORT=33077 TYPECHO_DB_USER=root TYPECHO_DB_PASSWORD= TYPECHO_DB_DATABASE=typecho TYPECHO_DB_CHARSET=utf8mb4 TYPECHO_DB_PREFIX=typecho_ TYPECHO_SITE_URL=http://localhost:8101 TYPECHO_USER_NAME=admin TYPECHO_USER_PASSWORD=admin123 TYPECHO_USER_MAIL=admin@example.com php -d error_reporting=8191 install.php >/dev/null 2>&1)
  mysql -h127.0.0.1 -P33077 -uroot typecho -e "UPDATE typecho_options SET value='Nimbus' WHERE name='theme'"
  php -d error_reporting=8191 -S 127.0.0.1:8101 -t "$W/tc-mysql" "$ROOT/tests/router.php" >"$W/php-mysql.log" 2>&1 & pids+=($!)
  sleep 1; BASE=http://localhost:8101 bash "$ROOT/tests/activate-kit.sh" >/dev/null || BASE=http://localhost:8101 bash "$ROOT/tests/activate-kit.sh"
  T=$(( $(date +%s) - 120 ))
  { seed_sql $T; pages_sql $T '`order`' 'authorId'; } | mysql -h127.0.0.1 -P33077 -uroot typecho
  run_checks 8101 mysql
fi

if [ "$WHICH" = all ] || [ "$WHICH" = pgsql ]; then
  echo "== PostgreSQL =="
  LC_ALL=C "$PGBIN/initdb" -D "$W/pg" -U postgres -A trust --locale=C -E UTF8 >/dev/null 2>&1
  LC_ALL=C "$PGBIN/pg_ctl" -D "$W/pg" -o "-p 54377 -c unix_socket_directories='' -c listen_addresses=127.0.0.1" -l "$W/pg.log" start >/dev/null 2>&1
  for i in $(seq 1 30); do "$PGBIN/psql" -h 127.0.0.1 -p 54377 -U postgres -c 'select 1' >/dev/null 2>&1 && break; sleep 1; done
  "$PGBIN/psql" -h 127.0.0.1 -p 54377 -U postgres -q -c 'CREATE DATABASE typecho'
  prepare_copy "$W/tc-pg"
  (cd "$W/tc-pg" && TYPECHO_DB_ADAPTER=Pdo_Pgsql TYPECHO_DB_HOST=127.0.0.1 TYPECHO_DB_PORT=54377 TYPECHO_DB_USER=postgres TYPECHO_DB_PASSWORD= TYPECHO_DB_DATABASE=typecho TYPECHO_DB_PREFIX=typecho_ TYPECHO_SITE_URL=http://localhost:8102 TYPECHO_USER_NAME=admin TYPECHO_USER_PASSWORD=admin123 TYPECHO_USER_MAIL=admin@example.com php -d error_reporting=8191 install.php >/dev/null 2>&1)
  "$PGBIN/psql" -h 127.0.0.1 -p 54377 -U postgres typecho -q -c "UPDATE typecho_options SET value='Nimbus' WHERE name='theme'"
  php -d error_reporting=8191 -S 127.0.0.1:8102 -t "$W/tc-pg" "$ROOT/tests/router.php" >"$W/php-pg.log" 2>&1 & pids+=($!)
  sleep 1; BASE=http://localhost:8102 bash "$ROOT/tests/activate-kit.sh" >/dev/null || BASE=http://localhost:8102 bash "$ROOT/tests/activate-kit.sh"
  T=$(( $(date +%s) - 120 ))
  { seed_sql $T; pages_sql $T '"order"' '"authorId"'; } | "$PGBIN/psql" -h 127.0.0.1 -p 54377 -U postgres typecho -q
  run_checks 8102 pgsql
fi

[ $fail -eq 0 ] && echo "multi-db ok"
exit $fail
