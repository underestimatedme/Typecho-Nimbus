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
$pages = [
    ['说说', 'moments', 'page-moments.php', '生活的碎片，随手记下。'], ['相册', 'albums', 'page-albums.php', ''],
    ['收藏夹', 'bookmarks', 'page-bookmarks.php', '收集常用的好用站点和工具。'], ['项目', 'projects', 'page-projects.php', '从零到一，用代码构建的每一份作品。'],
    ['留言板', 'messages', 'page-messages.php', '有什么想说的，留在这里吧。'], ['时间线', 'timeline', 'page-timeline.php', ''],
    ['音乐', 'music', 'page-music.php', '给专注一点节奏，给灵感一段旋律。'], ['统计', 'stats', 'page-stats.php', '记录每一次写作与相遇。'],
];
foreach ($pages as [$title, $slug, $tpl, $text]) {
    $st = $db->prepare("SELECT COUNT(*) FROM {$p}contents WHERE slug=? AND type='page'"); $st->execute([$slug]);
    if ((int)$st->fetchColumn() === 0) {
        $db->prepare("INSERT INTO {$p}contents (title,slug,created,modified,text,\"order\",authorId,template,type,status,password,commentsNum,allowComment,allowPing,allowFeed,parent) VALUES (?,?,?,?,?,0,1,?,'page','publish',NULL,0,'1','1','1',0)")
            ->execute([$title, $slug, $now - 300, $now - 300, '<!--markdown-->' . $text, $tpl]);
    }
}
$tableExists = function (string $t) use ($db, $p) { return (bool)$db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$p}$t'")->fetchColumn(); };
if ($tableExists('nimbus_bookmarks') && !$has('nimbus_bookmarks')) {
    $rows = [
        ['AI 工具', 'ChatGPT', 'https://chatgpt.com', '', '通用对话助手', 1], ['AI 工具', 'Claude', 'https://claude.ai', '', 'Anthropic 的 AI 助手', 2],
        ['AI 工具', 'DeepSeek', 'https://chat.deepseek.com', '', '深度求索', 3], ['开发', 'GitHub', 'https://github.com', 'https://github.com/favicon.ico', '代码托管', 10],
        ['开发', 'MDN', 'https://developer.mozilla.org', '', 'Web 技术文档', 11], ['开发', 'Can I use', 'https://caniuse.com', '', '浏览器兼容性查询', 12],
        ['设计', 'Dribbble', 'https://dribbble.com', '', '设计灵感', 20], ['设计', 'Unsplash', 'https://unsplash.com', '', '免费高清图片', 21],
    ];
    foreach ($rows as [$g, $n, $u, $i, $d, $so]) $db->prepare("INSERT INTO {$p}nimbus_bookmarks (group_name,name,url,icon,description,sort,status,created) VALUES (?,?,?,?,?,?,'publish',?)")->execute([$g, $n, $u, $i, $d, $so, $now]);
}
if ($tableExists('nimbus_projects') && !$has('nimbus_projects')) {
    $rows = [
        ['Nimbus', '开源免费的 Typecho 主题：三套皮肤、沉浸式阅读、无刷新切换。', 'https://picsum.photos/id/1015/800/450', 'https://example.com', 'https://github.com/underestimatedme/Nimbus', 'PHP,CSS,JavaScript,Typecho', 1],
        ['NimbusKit', 'Nimbus 配套插件：浏览量、点赞、即时搜索、邮件通知、说说与相册。', 'https://picsum.photos/id/1036/800/450', '', 'https://github.com/underestimatedme/Nimbus', 'PHP,SQLite,MySQL,PostgreSQL', 2],
        ['小工具合集', '日常写的一些命令行小工具。', '', '', '', 'Python,Bash', 3],
    ];
    foreach ($rows as [$n, $d, $c, $u, $r, $t, $so]) $db->prepare("INSERT INTO {$p}nimbus_projects (name,description,cover,url,repo,tags,sort,status,created) VALUES (?,?,?,?,?,?,?,'publish',?)")->execute([$n, $d, $c, $u, $r, $t, $so, $now]);
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
$st = $db->prepare("SELECT COUNT(*) FROM {$p}contents WHERE slug='shortcode-demo'"); $st->execute();
if ((int)$st->fetchColumn() === 0) {
    $md = "<!--markdown-->这篇文章演示 Nimbus 的短代码。\n\n## 提示框\n\n[tip]这是默认的信息提示。[/tip]\n\n[tip type=\"success\"]\n操作成功！支持**多行**和 Markdown。\n[/tip]\n\n[tip type=\"warning\"]注意：这是警告。[/tip]\n\n[tip type=\"danger\"]危险操作，请三思。[/tip]\n\n## 折叠块\n\n[collapse title=\"点击展开详细说明\"]\n折叠块里的内容，适合放长篇补充。\n[/collapse]\n\n## 标签页\n\n[tabs]\n[tab title=\"macOS\"]使用 Homebrew 安装：`brew install php`[/tab]\n[tab title=\"Ubuntu\"]使用 apt 安装：`apt install php`[/tab]\n[tab title=\"Windows\"]下载官方压缩包解压即可。[/tab]\n[/tabs]\n\n## 按钮\n\n[button url=\"https://typecho.org\"]访问 Typecho 官网[/button] [button url=\"/\" type=\"ghost\"]回到首页[/button]\n\n## GitHub 卡片\n\n[github repo=\"typecho/typecho\"]\n\n## B 站视频\n\n[bilibili bv=\"BV1GJ411x7h7\"]\n\n## 回复可见\n\n[hide]\n恭喜你看到了隐藏内容：下载密码是 nimbus2026。\n[/hide]\n\n## 代码块里的短代码不解析\n\n```\n[tip]我不会被解析[/tip]\n```\n";
    $t = $now - 600;
    $db->prepare("INSERT INTO {$p}contents (title,slug,created,modified,text,\"order\",authorId,template,type,status,password,commentsNum,allowComment,allowPing,allowFeed,parent) VALUES (?,?,?,?,?,0,1,NULL,'post','publish',NULL,0,'1','1','1',0)")->execute(['短代码演示', 'shortcode-demo', $t, $t, $md]);
    $cid = (int)$db->lastInsertId();
    $mid = (int)$db->query("SELECT mid FROM {$p}metas WHERE slug='tech' AND type='category'")->fetchColumn();
    if ($mid) $db->prepare("INSERT INTO {$p}relationships (cid,mid) VALUES (?,?)")->execute([$cid, $mid]);
}
echo "seed-kit: ok\n";
