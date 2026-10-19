#!/usr/bin/env bash
# Ensure a scheduled (future-dated) published post is not shown in the homepage's latest stack.
set -euo pipefail
DB="$1"; BASE="$2"
SLUG="nimbus-future-regression-$(date +%s)-$$"
TITLE="Nimbus Future Regression $SLUG"

cleanup() {
  php -r '
$db = new PDO("sqlite:" . $argv[1]);
$db->beginTransaction();
$st = $db->prepare("SELECT cid FROM typecho_contents WHERE slug=?");
$st->execute([$argv[2]]);
$cid = $st->fetchColumn();
if ($cid) {
    foreach (["typecho_relationships", "typecho_fields", "typecho_comments"] as $table) {
        $del = $db->prepare("DELETE FROM $table WHERE cid=?");
        $del->execute([$cid]);
    }
    $del = $db->prepare("DELETE FROM typecho_contents WHERE cid=?");
    $del->execute([$cid]);
}
$db->commit();
' "$DB" "$SLUG"
}
trap cleanup EXIT

php -r '
$db = new PDO("sqlite:" . $argv[1]);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$st = $db->prepare("SELECT mid FROM typecho_metas WHERE type=? ORDER BY mid LIMIT 1");
$st->execute(["category"]);
$mid = $st->fetchColumn();
$created = time() + 30 * 86400;
$st = $db->prepare("INSERT INTO typecho_contents (title,slug,created,modified,text,\"order\",authorId,template,type,status,password,commentsNum,allowComment,allowPing,allowFeed,parent) VALUES (?,?,?,?,?,0,1,NULL,?,?,NULL,0,?,?,?,0)");
$st->execute([$argv[3], $argv[2], $created, $created, "Scheduled future-post regression fixture", "post", "publish", "1", "1", "1"]);
$cid = (int)$db->lastInsertId();
$db->prepare("INSERT INTO typecho_relationships (cid,mid) VALUES (?,?)")->execute([$cid, (int)$mid]);
' "$DB" "$SLUG" "$TITLE"

body="$(curl -fsS "$BASE/")"
if printf '%s' "$body" | grep -Fq "$TITLE"; then
  echo "FAIL homepage latest stack included future post: $TITLE"
  exit 1
fi
echo "future scheduled post hidden from home"
