#!/usr/bin/env bash
# Integration regression: identical note and site description should not repeat in both Bento tiles.
set -euo pipefail
DB="$1"; BASE="$2"; SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
BACKUP="$(mktemp)"

php -r '
$db = new PDO("sqlite:" . $argv[1]);
$st = $db->prepare("SELECT name,user,value FROM typecho_options WHERE user=0 AND name IN (?, ?, ?, ?, ?)");
$st->execute(["description", "NoticeHtml", "MusicEnable", "Language", "theme:Nimbus"]);
file_put_contents($argv[2], serialize($st->fetchAll(PDO::FETCH_ASSOC)));
' "$DB" "$BACKUP"

restore() {
  php -r '
$db = new PDO("sqlite:" . $argv[1]);
$names = ["description", "NoticeHtml", "MusicEnable", "Language", "theme:Nimbus"];
$db->beginTransaction();
$st = $db->prepare("DELETE FROM typecho_options WHERE user=0 AND name=?");
foreach ($names as $name) $st->execute([$name]);
foreach (unserialize(file_get_contents($argv[2]), ["allowed_classes" => false]) as $row) {
    $insert = $db->prepare("INSERT INTO typecho_options (name,user,value) VALUES (?, ?, ?)");
    $insert->execute([$row["name"], $row["user"], $row["value"]]);
}
$db->commit();
' "$DB" "$BACKUP"
  rm -f "$BACKUP"
}
trap restore EXIT

body="$(curl -fsS "$BASE/")"
shared="$(printf '%s' "$body" | php -r '
$html = stream_get_contents(STDIN);
if (preg_match("~class=\"[^\"]*\\bn-bento-note\\b[^\"]*\"[\\s\\S]*?<span class=\"n-bento-quote\">(.*?)</span>~s", $html, $m)) {
    echo mb_substr(trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, "UTF-8")), 0, 80);
}
')"
shared="${shared:-Nimbus 测试公告}"
php "$SCRIPT_DIR/set-theme-opt.php" "$DB" "description=$shared" "NoticeHtml=$shared" 'MusicEnable=0' 'Language=zh' >/dev/null
body="$(curl -fsS "$BASE/")"
note="$(printf '%s' "$body" | php -r '
$html = stream_get_contents(STDIN);
if (preg_match("~class=\"[^\"]*\\bn-bento-note\\b[^\"]*\"[\\s\\S]*?<span class=\"n-bento-quote\">(.*?)</span>~s", $html, $m)) {
    echo html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, "UTF-8");
}
')"
motto="$(printf '%s' "$body" | php -r '
$html = stream_get_contents(STDIN);
if (preg_match("~class=\"[^\"]*\\bn-bento-motto\\b[^\"]*\"[\\s\\S]*?<span class=\"n-bento-quote\">(.*?)</span>~s", $html, $m)) {
    echo html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, "UTF-8");
}
')"
[ "$note" = "$shared" ] || { printf 'FAIL left note tile should keep its text; expected %s, got %s\n' "$shared" "$note"; exit 1; }
printf '%s' "$motto" | grep -q '记录、思考，与世界温柔相处' || { printf 'FAIL duplicate motto should use the default short phrase; got %s\n' "$motto"; exit 1; }
echo 'home bento duplicate ok'
