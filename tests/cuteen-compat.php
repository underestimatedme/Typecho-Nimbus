<?php
/** Real Typecho rendering against an isolated copy of the local SQLite fixture. */
declare(strict_types=1);
$root = dirname(__DIR__);
$source = $root . '/.dev/typecho';
if (!is_file($source . '/config.inc.php')) { fwrite(STDERR, "Run tests/dev-site.sh first\n"); exit(1); }
$site = sys_get_temp_dir() . '/nimbus-cuteen-' . bin2hex(random_bytes(4));
mkdir($site . '/usr/themes', 0777, true);
mkdir($site . '/usr/plugins');
foreach (['var', 'index.php'] as $file) symlink($source . '/' . $file, $site . '/' . $file);
// admin/common.php resolves its config with __DIR__, so PHP files must be real copies.
mkdir($site . '/admin');
foreach (glob($source . '/admin/*') as $file) {
    $target = $site . '/admin/' . basename($file);
    if (is_dir($file)) symlink($file, $target); else copy($file, $target);
}
symlink($root . '/Nimbus', $site . '/usr/themes/Nimbus');
copy($source . '/usr/nimbus.db', $site . '/usr/nimbus.db');
$config = str_replace($source . '/usr/nimbus.db', $site . '/usr/nimbus.db', file_get_contents($source . '/config.inc.php'));
file_put_contents($site . '/config.inc.php', $config);
$db = new PDO('sqlite:' . $site . '/usr/nimbus.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
$port = (int)substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
fclose($socket);
$base = 'http://127.0.0.1:' . $port;
$set = $db->prepare('UPDATE typecho_options SET value=? WHERE name=? AND user=0');
$set->execute([$base, 'siteUrl']);
$set->execute([serialize(['activated' => [], 'handles' => []]), 'plugins']);
$set->execute(['1', 'commentsPageBreak']);
$set->execute(['2', 'commentsPageSize']);
$settings = unserialize($db->query("SELECT value FROM typecho_options WHERE name='theme:Nimbus'")->fetchColumn(), ['allowed_classes' => false]);
$settings['CustomMenu'] = '{"name":"编程","children":[{"name":"Go","url":"/category/tech/"}]}, {"name":"闪念","url":"/blink.html"}';
$set->execute([serialize($settings), 'theme:Nimbus']);
$reset = proc_open([PHP_BINARY, $root . '/tests/reset-admin.php', $site . '/usr/nimbus.db'], [['file','/dev/null','r'], ['file','/dev/null','w'], ['file','/dev/null','w']], $resetPipes);
proc_close($reset);
$insert = $db->prepare('INSERT INTO typecho_contents (cid,title,slug,created,modified,text,authorId,type,status,template,allowComment,allowPing,allowFeed,commentsNum) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
$insert->execute([99001,'闪念','blink',100,100,'',1,'page','publish','page-soliloquize.php',1,0,0,3]);
$insert->execute([99002,'旧封面','legacy-cover',time()-10,time()-10,'正文没有图片',1,'post','publish',null,1,0,1,0]);
$db->prepare('INSERT INTO typecho_fields (cid,name,type,str_value) VALUES (?,?,?,?)')->execute([99002,'imgst','str','https://img.example/old-cover.jpg']);
$insert->execute([99003,'相关旧封面','legacy-related',time()-20,time()-20,'相关正文',1,'post','publish',null,1,0,1,0]);
$db->prepare('INSERT INTO typecho_fields (cid,name,type,str_value) VALUES (?,?,?,?)')->execute([99003,'imgst','str','https://img.example/related-cover.jpg']);
$mid = (int)$db->query("SELECT mid FROM typecho_metas WHERE slug='tech' AND type='category'")->fetchColumn();
$relation = $db->prepare('INSERT INTO typecho_relationships (cid,mid) VALUES (?,?)');
$relation->execute([99002,$mid]); $relation->execute([99003,$mid]);
$comment = $db->prepare('INSERT INTO typecho_comments (coid,cid,created,author,authorId,ownerId,text,type,status,parent) VALUES (?,?,?,?,?,?,?,?,?,?)');
foreach ([[99101,99001,300,'NEWEST','approved',0], [99102,99001,100,'OLDEST','approved',0], [99103,99001,200,'MIDDLE','approved',0], [99104,99001,400,'PRIVATE','waiting',0], [99105,99002,500,'OTHER_PAGE','approved',0], [99106,99001,600,'REPLY','approved',99101], [99107,99001,700,'TRACKBACK','approved',0]] as $r) {
    $comment->execute([$r[0],$r[1],$r[2],'outlier',1,1,$r[3],$r[3] === 'TRACKBACK' ? 'trackback' : 'comment',$r[4],$r[5]]);
}
$pipes = [];
$server = proc_open([PHP_BINARY, '-d', 'error_reporting=8191', '-S', '127.0.0.1:' . $port, '-t', $site, $root . '/tests/router.php'], [['pipe','r'], ['file',$site . '/server.log','a'], ['file',$site . '/server.log','a']], $pipes);
$fail = 0;
function check_compat(bool $condition, string $name): void { global $fail; echo ($condition ? 'ok ' : 'FAIL ') . $name . "\n"; if (!$condition) $fail++; }
function get_compat(string $path): string { global $base; return (string)file_get_contents($base . $path, false, stream_context_create(['http' => ['ignore_errors' => true]])); }
function auth_compat(string $url, ?string $post = null, string $referer = ''): string {
    global $site;
    $cmd = ['curl', '-s', '-D', $site . '/headers', '-c', $site . '/cookies', '-b', $site . '/cookies'];
    if ($referer !== '') { $cmd[] = '-e'; $cmd[] = $referer; }
    if ($post !== null) { $cmd[] = '-d'; $cmd[] = $post; }
    $cmd[] = $url;
    $proc = proc_open($cmd, [['pipe','r'], ['pipe','w'], ['pipe','w']], $pipes);
    fclose($pipes[0]); $body = stream_get_contents($pipes[1]); fclose($pipes[1]); fclose($pipes[2]); proc_close($proc);
    return $body;
}
try {
    for ($i=0; $i<40; $i++) { $ready = @fsockopen('127.0.0.1', $port); if ($ready) { fclose($ready); break; } usleep(50000); }
    $html = get_compat('/index.php/blink.html');
    check_compat(strpos($html, 'n-moments') !== false && strpos($html, 'n-comment-list') === false, 'old template renders as moments without NimbusKit');
    check_compat(strpos($html, 'NEWEST') !== false && strpos($html, 'MIDDLE') > strpos($html, 'NEWEST') && strpos($html, 'OLDEST') === false, 'created DESC precedes comment ID and paginates from newest');
    check_compat(strpos($html, 'PRIVATE') === false && strpos($html, 'OTHER_PAGE') === false && strpos($html, 'REPLY') === false && strpos($html, 'TRACKBACK') === false, 'only approved root comments of this page are public');
    check_compat(strpos($html, 'data-comment-form') === false && strpos($html, '发表评论') === false, 'anonymous visitors see no comment publisher');
    check_compat(strpos($html, 'mp=2') !== false, 'load more points to second moments page');
    $second = get_compat('/index.php/blink.html?mp=2');
    check_compat(strpos($second, 'OLDEST') !== false && strpos($second, 'NEWEST') === false && strpos($second, 'data-load-more') === false, 'second page shows remaining old moment exactly once');
    $cover = get_compat('/index.php/archives/99002/');
    check_compat(strpos($cover, 'https://img.example/old-cover.jpg') !== false && strpos($cover, 'n-post-cover has-image') !== false, 'legacy imgst supplies article cover');
    check_compat(strpos($cover, '>编程') !== false && strpos($cover, '>Go') !== false && strpos($cover, '/blink.html') !== false, 'legacy menu works in rendered navigation');
    $home = get_compat('/');
    check_compat(strpos($home, 'https://img.example/old-cover.jpg') !== false, 'row-based homepage cards retain legacy cover');
    check_compat((bool)preg_match('#class="n-related-item"[^>]*archives/99003/[^>]*>\s*<i[^>]*https://img.example/related-cover.jpg#', $cover), 'related articles retain legacy cover');
    $settings['HomeStyle'] = 'classic'; $settings['TopPosts'] = '99002';
    $set->execute([serialize($settings), 'theme:Nimbus']);
    $carousel = get_compat('/');
    check_compat((bool)preg_match('#class="n-slide-bg"[^>]*https://img.example/old-cover.jpg#', $carousel), 'pinned carousel retains legacy cover');
    $db->exec("UPDATE typecho_contents SET password='secret' WHERE cid=99001");
    $locked = get_compat('/index.php/blink.html');
    check_compat(strpos($locked, 'NEWEST') === false && strpos($locked, 'MIDDLE') === false && strpos($locked, 'n-password') !== false, 'password protected moments do not leak');
    $db->exec("UPDATE typecho_contents SET password=NULL WHERE cid=99001");
    $login = auth_compat($base . '/admin/login.php');
    preg_match('#action/login\?_=[a-f0-9]+#', $login, $match);
    auth_compat($base . '/index.php/' . ($match[0] ?? ''), 'name=admin&password=admin123', $base . '/admin/login.php');
    $owner = auth_compat($base . '/index.php/blink.html');
    check_compat(strpos($owner, 'data-comment-form') !== false && strpos($owner, '发布闪念') !== false, 'owner can publish through native comment form');
    preg_match('#<form[^>]+action="([^"]+)"[^>]*data-comment-form#', $owner, $form);
    preg_match('#name="_" value="([^"]+)"#', $owner, $token);
    auth_compat(html_entity_decode($form[1] ?? '', ENT_QUOTES), http_build_query(['_' => $token[1] ?? '', 'text' => 'PUBLISHED_MOMENT']), $base . '/index.php/blink.html');
    $published = get_compat('/index.php/blink.html');
    check_compat(strpos($published, 'PUBLISHED_MOMENT') !== false && strpos($published, 'PUBLISHED_MOMENT') < strpos($published, 'NEWEST'), 'newly published moment appears first');
    // Simulate a Cuteen installation, then exercise Typecho's actual theme activation action.
    mkdir($site . '/usr/themes/Cuteen_bak2');
    file_put_contents($site . '/usr/themes/Cuteen_bak2/index.php', "<?php\n/**\n * Cuteen fixture\n * @package Cuteen\n */\n");
    $legacy = ['CustomMenu' => '{"name":"原菜单","url":"/old-menu"}', 'RandImg' => 'https://img.example/pool.jpg', 'LabelOrder' => 'P-C', 'MergePages' => '独立页面'];
    $db->prepare('INSERT INTO typecho_options (name,user,value) VALUES (?,0,?)')->execute(['theme:Cuteen_bak2', serialize($legacy)]);
    $set->execute(['Cuteen_bak2', 'theme']);
    $themes = auth_compat($base . '/admin/themes.php');
    preg_match('#href="([^"]*themes-edit\?change=Nimbus[^"]*)"#', $themes, $activate);
    check_compat(isset($activate[1]), 'theme activation link found');
    if (isset($activate[1])) auth_compat(html_entity_decode($activate[1], ENT_QUOTES), null, $base . '/admin/themes.php');
    $saved = unserialize($db->query("SELECT value FROM typecho_options WHERE name='theme:Nimbus' AND user=0")->fetchColumn(), ['allowed_classes' => false]);
    check_compat(($saved['ThumbPool'] ?? '') === 'https://img.example/pool.jpg' && ($saved['MenuSource'] ?? '') === 'pages-first' && ($saved['MenuPageGroup'] ?? '') === '独立页面', 'actual theme switch inherits Cuteen pool and default menu settings');
    check_compat(strpos(get_compat('/'), '>原菜单') !== false && !$db->query("SELECT name FROM typecho_options WHERE name='theme:Cuteen_bak2'")->fetchColumn(), 'menu survives deletion of old theme settings');
    $saved['CustomMenu'] = ''; $saved['MenuPageGroup'] = ''; $saved['MenuSource'] = 'categories-first';
    $set->execute([serialize($saved), 'theme:Nimbus']);
    $defaultMenu = get_compat('/');
    check_compat(strpos($defaultMenu, '/category/tech/') !== false && strpos($defaultMenu, '>技术') !== false && strpos($defaultMenu, '/blink.html') !== false, 'default navigation includes categories and existing pages');
} finally {
    proc_terminate($server); proc_close($server);
    foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe);
    $db = null;
    // This temporary directory contains only test-owned files and symlinks.
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($site, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) { if ($f->isDir() && !$f->isLink()) rmdir($f->getPathname()); else unlink($f->getPathname()); }
    rmdir($site);
}
exit($fail ? 1 : 0);
