<?php
/**
 * NimbusKit 示例数据（幂等）：说说、友链、相册，以及「说说」「相册」独立页。
 * 用法：php tests/seed-kit.php <sqlite文件>
 */
$db = new PDO('sqlite:' . $argv[1]);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$p = 'typecho_';
$has = function (string $t) use ($db, $p) { return (int)$db->query("SELECT COUNT(*) FROM {$p}$t")->fetchColumn() > 0; };
$now = time();
foreach ([['说说', 'moments', 'page-moments.php', '生活的碎片，随手记下。'], ['相册', 'albums', 'page-albums.php', '']] as [$title, $slug, $tpl, $text]) {
    $st = $db->prepare("SELECT COUNT(*) FROM {$p}contents WHERE slug=? AND type='page'"); $st->execute([$slug]);
    if ((int)$st->fetchColumn() === 0) {
        $db->prepare("INSERT INTO {$p}contents (title,slug,created,modified,text,\"order\",authorId,template,type,status,password,commentsNum,allowComment,allowPing,allowFeed,parent) VALUES (?,?,?,?,?,0,1,?,'page','publish',NULL,0,'1','1','1',0)")
            ->execute([$title, $slug, $now, $now, '<!--markdown-->' . $text, $tpl]);
    }
}
if ((int)$db->query("SELECT COUNT(*) FROM {$p}nimbus_moments")->fetchColumn() < 3) {
    $rows = [
        ['秋天第一杯热可可，配一本旧书。', ['https://picsum.photos/id/1060/800/800'], 3600],
        ["今天给 Nimbus 加了沉浸式文章头图。\n大屏终于不空了 ✨", [], 86400],
        ['周末去海边，风很大，云很好看。', ['https://picsum.photos/id/1011/800/800', 'https://picsum.photos/id/1015/800/800', 'https://picsum.photos/id/1016/800/800', 'https://picsum.photos/id/1018/800/800', 'https://picsum.photos/id/1019/800/800', 'https://picsum.photos/id/1021/800/800'], 86400 * 3],
        ['推荐一个好用的博客程序 https://typecho.org', [], 86400 * 6],
        ['阳台的绿萝又长高了一截。', ['https://picsum.photos/id/1080/800/800', 'https://picsum.photos/id/1082/800/800'], 86400 * 9],
    ];
    foreach ($rows as [$c, $imgs, $ago]) $db->prepare("INSERT INTO {$p}nimbus_moments (content,images,created,status) VALUES (?,?,?,'publish')")->execute([$c, json_encode($imgs, JSON_UNESCAPED_SLASHES), $now - $ago]);
}
if (!$has('nimbus_links')) {
    $rows = [
        ['Typecho', 'https://typecho.org', 'https://typecho.org/favicon.ico', '轻量开源博客程序', 1],
        ['云间客', 'https://example.com', '', '热爱生活，偶尔写代码', 2],
        ['Nimbus', 'https://github.com/underestimatedme/Nimbus', 'https://invalid.invalid/x.png', '本主题仓库', 3],
        ['小明的博客', 'https://example.org', '', '记录学习与成长', 4],
    ];
    foreach ($rows as [$n, $u, $a, $d, $s]) $db->prepare("INSERT INTO {$p}nimbus_links (name,url,avatar,description,sort,status,created) VALUES (?,?,?,?,?,'publish',?)")->execute([$n, $u, $a, $d, $s, $now]);
}
if (!$has('nimbus_albums')) {
    $mk = function (array $ids, string $cap) { return array_map(function ($i, $k) use ($cap) { return ['url' => "https://picsum.photos/id/$i/900/" . (600 + ($k % 3) * 150), 'caption' => $cap . ' ' . ($k + 1)]; }, $ids, array_keys($ids)); };
    $rows = [
        ['山与湖', '远行路上的风景', $mk([1015, 1016, 1018, 1036, 1039, 1043, 1044], '山湖'), 1],
        ['城市夜色', '霓虹与雨夜', $mk([1067, 1069, 1073, 1076, 1078], '夜色'), 2],
        ['日常', '', $mk([1060, 1080, 1082, 1084], '日常'), 3],
    ];
    foreach ($rows as [$t, $d, $ph, $s]) $db->prepare("INSERT INTO {$p}nimbus_albums (title,cover,description,photos,sort,created) VALUES (?,'',?,?,?,?)")->execute([$t, $d, json_encode($ph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $s, $now]);
}
echo "seed-kit: ok\n";
