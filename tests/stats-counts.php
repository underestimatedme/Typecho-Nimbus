<?php
/** Real Typecho regression: overlapping categories must not inflate the post total. */
declare(strict_types=1);
$root = dirname(__DIR__);
$source = $root . '/.dev/typecho';
if (!is_file($source . '/config.inc.php')) { fwrite(STDERR, "Run tests/dev-site.sh first\n"); exit(1); }
$site = sys_get_temp_dir() . '/nimbus-stats-' . bin2hex(random_bytes(4));
mkdir($site . '/usr/themes', 0777, true);
mkdir($site . '/usr/plugins');
foreach (['var', 'index.php'] as $file) symlink($source . '/' . $file, $site . '/' . $file);
symlink($root . '/Nimbus', $site . '/usr/themes/Nimbus');
copy($source . '/usr/nimbus.db', $site . '/usr/nimbus.db');
file_put_contents($site . '/config.inc.php', str_replace($source . '/usr/nimbus.db', $site . '/usr/nimbus.db', file_get_contents($source . '/config.inc.php')));
$db = new PDO('sqlite:' . $site . '/usr/nimbus.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
$port = (int)substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
fclose($socket);
$base = 'http://127.0.0.1:' . $port;
$set = $db->prepare('UPDATE typecho_options SET value=? WHERE name=? AND user=0');
$set->execute([$base, 'siteUrl']);
$set->execute([serialize(['activated' => [], 'handles' => []]), 'plugins']);
$set->execute(['Nimbus', 'theme']);
$settings = unserialize($db->query("SELECT value FROM typecho_options WHERE name='theme:Nimbus' AND user=0")->fetchColumn(), ['allowed_classes' => false]);
$settings = is_array($settings) ? $settings : [];
$settings['Language'] = 'zh';
$set->execute([serialize($settings), 'theme:Nimbus']);
$set->execute(['zh', 'Language']);
foreach (['contents', 'metas', 'relationships', 'comments', 'fields'] as $table) $db->exec('DELETE FROM typecho_' . $table);
$meta = $db->prepare('INSERT INTO typecho_metas (mid,name,slug,type,count,parent) VALUES (?,?,?,?,?,0)');
foreach ([[1,'Category A','stats-a','category',999], [2,'Category B','stats-b','category',777], [3,'Future category','stats-future','category',555], [4,'Public tag','stats-tag','tag',444], [5,'Future tag','stats-future-tag','tag',333]] as $row) $meta->execute($row);
$post = $db->prepare('INSERT INTO typecho_contents (cid,title,slug,type,status,created,modified,text,password,authorId,template,allowComment,allowPing,allowFeed,commentsNum) VALUES (?,?,?,?,?,?,?,\'fixture\',?,1,?,0,0,0,0)');
$now = time();
foreach ([
    [101,'Post A','post-a','post','publish',$now - 100,null,null],
    [102,'Post B','post-b','post','publish',$now - 100,null,null],
    [103,'Uncategorized','uncategorized','post','publish',$now - 100,null,null],
    [104,'Scheduled','scheduled','post','publish',$now + 86400,null,null],
    [105,'Draft','draft','post','draft',$now - 100,null,null],
    [106,'Stats','stats','page','publish',$now - 100,null,'page-stats.php'],
    [107,'Protected','protected','post','publish',$now - 100,'secret',null],
    [108,'Private','private','post','private',$now - 100,null,null],
    [109,'Revision','revision','post_draft','publish',$now - 100,null,null],
] as $row) $post->execute([$row[0],$row[1],$row[2],$row[3],$row[4],$row[5],$now,$row[6],$row[7]]);
$relation = $db->prepare('INSERT INTO typecho_relationships (cid,mid) VALUES (?,?)');
foreach ([[101,1],[101,2],[101,4],[102,1],[102,2],[102,4],[104,1],[104,3],[104,4],[104,5],[105,1],[106,1],[107,1],[108,1],[109,1]] as $row) $relation->execute($row);
$server = proc_open([PHP_BINARY, '-d', 'error_reporting=8191', '-S', '127.0.0.1:' . $port, '-t', $site, $root . '/tests/router.php'], [['pipe','r'], ['file',$site . '/server.log','a'], ['file',$site . '/server.log','a']], $pipes);
$fail = 0;
function check_stats(bool $ok, string $name): void { global $fail; echo ($ok ? 'ok ' : 'FAIL ') . $name . "\n"; if (!$ok) $fail++; }
try {
    for ($i = 0; $i < 40; $i++) { $ready = @fsockopen('127.0.0.1', $port); if ($ready) { fclose($ready); break; } usleep(50000); }
    $html = (string)file_get_contents($base . '/index.php/stats.html');
    check_stats(!preg_match('/Warning:|Notice:|Fatal error|Parse error/', $html), 'stats page renders without PHP errors');
    $doc = new DOMDocument();
    @$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xp = new DOMXPath($doc);
    $panel = $xp->query('//section[header/h2[text()="分类分布"]]')->item(0);
    check_stats($panel !== null, 'category chart is rendered');
    if ($panel) {
        check_stats(trim($xp->query('.//*[@class="n-ch-center"]', $panel)->item(0)->textContent) === '3', 'donut counts three unique public posts, including an uncategorized post');
        $counts = [];
        foreach ($xp->query('.//ul[@class="n-ch-legend"]/li', $panel) as $li) {
            $counts[$xp->query('.//span', $li)->item(0)->textContent] = (int)$xp->query('.//b', $li)->item(0)->textContent;
        }
        ksort($counts);
        check_stats($counts === ['Category A' => 2, 'Category B' => 2], 'categories count actual public memberships and ignore stale metadata totals');
        check_stats($xp->query('.//em[text()="50%"]', $panel)->length === 2, 'category shares use the four memberships as denominator');
    }
    $postCount = $xp->query('(//div[contains(@class,"n-st-card")]//span[@data-count])[1]')->item(0);
    check_stats($postCount && $postCount->getAttribute('data-count') === '3', 'summary count excludes future, draft, protected, private and non-post content');
    $tags = $xp->query('//div[@class="n-st-tags"]/a');
    check_stats($tags->length === 1 && $xp->query('./small', $tags->item(0))->item(0)->textContent === '2', 'tags use the same public post population');
} finally {
    proc_terminate($server); proc_close($server);
    foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe);
    $db = null;
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($site, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) { if ($file->isDir() && !$file->isLink()) rmdir($file->getPathname()); else unlink($file->getPathname()); }
    rmdir($site);
}
exit($fail > 0 ? 1 : 0);
