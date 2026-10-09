<?php
/**
 * 灌入测试数据（幂等）：分类、标签、文章、独立页、评论；启用 Nimbus 主题。
 * 用法：php tests/seed.php <sqlite文件> <站点URL>
 */
$db = new PDO('sqlite:' . $argv[1]);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$siteUrl = $argv[2] ?? 'http://127.0.0.1:8099';
$p = 'typecho_';
$now = time();

function opt(PDO $db, string $p, string $name, string $value): void {
    $st = $db->prepare("SELECT COUNT(*) FROM {$p}options WHERE name=? AND user=0");
    $st->execute([$name]);
    if ($st->fetchColumn() > 0) {
        $db->prepare("UPDATE {$p}options SET value=? WHERE name=? AND user=0")->execute([$value, $name]);
    } else {
        $db->prepare("INSERT INTO {$p}options (name,user,value) VALUES (?,0,?)")->execute([$name, $value]);
    }
}
opt($db, $p, 'theme', 'Nimbus');
opt($db, $p, 'siteUrl', $siteUrl);
opt($db, $p, 'commentsThreaded', '1');
opt($db, $p, 'commentsShowCommentOnly', '0');
opt($db, $p, 'rewrite', '0');

$done = $db->query("SELECT COUNT(*) FROM {$p}contents WHERE slug='nimbus-seed-marker'")->fetchColumn();
if ($done > 0) { echo "seed: already seeded\n"; exit(0); }

// 分类
$cats = [['default', '默认分类'], ['tech', '技术'], ['life', '生活'], ['photo', '摄影']];
$catIds = [];
foreach ($cats as [$slug, $name]) {
    $st = $db->prepare("SELECT mid FROM {$p}metas WHERE slug=? AND type='category'");
    $st->execute([$slug]);
    $mid = $st->fetchColumn();
    if (!$mid) {
        $db->prepare("INSERT INTO {$p}metas (name,slug,type,description,count,\"order\",parent) VALUES (?,?,'category','',0,0,0)")->execute([$name, $slug]);
        $mid = (int)$db->lastInsertId();
    }
    $catIds[$slug] = (int)$mid;
}
$tags = ['Typecho', 'CSS', 'PHP', '旅行', '读书', '设计'];
$tagIds = [];
foreach ($tags as $i => $name) {
    $db->prepare("INSERT INTO {$p}metas (name,slug,type,description,count,\"order\",parent) VALUES (?,?,'tag','',0,0,0)")->execute([$name, 'tag-' . ($i + 1)]);
    $tagIds[] = (int)$db->lastInsertId();
}

$lorem = "<!--markdown-->这是一段用于测试排版的中文段落。Nimbus 是一个开源免费的 Typecho 主题，追求精致的细节与克制的动效。我们在这里放一些 **加粗**、*斜体* 和 `行内代码`，再加一个[链接](https://typecho.org)。\n\n## 二级标题\n\n> 引用：给时光以生命，给岁月以文明。\n\n### 三级标题\n\n- 列表项一\n- 列表项二\n- 列表项三\n\n| 列 A | 列 B | 列 C |\n| --- | --- | --- |\n| 1 | 2 | 3 |\n| 4 | 5 | 6 |\n\n## 另一个二级标题\n\n再来一段正文，确保文章足够长以便测试目录与阅读进度。\n\n";
$img = "![风景](https://picsum.photos/id/1018/1200/700)\n\n";
$code = "```php\n<?php\nnamespace Nimbus;\n\nfinal class Demo\n{\n    public static function hello(string \$name): string\n    {\n        return \"Hello, {\$name}!\";\n    }\n}\n```\n\n```js\nconst a = [1, 2, 3].map(n => n * 2);\nconsole.log(a);\n```\n\n";
$longCode = "```bash\n" . implode("\n", array_map(fn($i) => "echo \"line $i\"", range(1, 45))) . "\n```\n\n";
$math = "行内公式 \\(E = mc^2\\)，块级公式：\n\n$$\n\\int_0^\\infty e^{-x^2} dx = \\frac{\\sqrt{\\pi}}{2}\n$$\n\n";
$mermaid = "```mermaid\ngraph TD;\n  A[开始] --> B{判断};\n  B -->|是| C[执行];\n  B -->|否| D[结束];\n```\n\n";

$posts = [
    ['从零搭建一个让人想一直写下去的博客', 'tech', $lorem . $img . $code . $lorem],
    ['在京都的雨季，重新学会慢下来', 'life', $lorem . "![京都](https://picsum.photos/id/1043/1200/700)\n\n" . $lorem],
    ['用 CSS 原生嵌套和容器查询重写了整个主题', 'tech', $lorem . $code . $longCode . $lorem],
    ['秋天的第一组胶片：柯达 Gold 200', 'photo', "![胶片](https://picsum.photos/id/1047/1200/700)\n\n" . $lorem],
    ['数学公式与图表渲染测试', 'tech', $lorem . $math . $mermaid . $lorem],
    ['把阳台改造成了一个小小的工作角', 'life', $lorem . "![阳台](https://picsum.photos/id/1057/1200/700)\n\n" . $lorem],
    ['一篇没有图片的纯文字随笔', 'default', $lorem . $lorem],
    ['密码保护的文章', 'default', $lorem, 'secret'],
    ['很长很长的长文：关于写作的十二个念头', 'life', str_repeat($lorem, 4)],
    ['读《设计中的设计》', 'default', $lorem],
    ['我的 2025 年度总结', 'life', $lorem . "![总结](https://picsum.photos/id/1050/1200/700)\n\n" . $lorem],
    ['Typecho 插件开发入门', 'tech', $lorem . $code],
    ['海边的一天', 'photo', "![海](https://picsum.photos/id/1036/1200/700)\n\n" . $lorem],
    ['关于本站的技术栈', 'tech', $lorem],
    ['第十五篇：用来测试分页', 'default', $lorem],
];
$cids = [];
foreach ($posts as $i => $row) {
    [$title, $cat, $text] = $row;
    $password = $row[3] ?? null;
    $created = $now - ($i * 86400 * 3);
    $db->prepare("INSERT INTO {$p}contents (title,slug,created,modified,text,\"order\",authorId,template,type,status,password,commentsNum,allowComment,allowPing,allowFeed,parent) VALUES (?,?,?,?,?,0,1,NULL,'post','publish',?,0,'1','1','1',0)")
        ->execute([$title, 'post-' . ($i + 1), $created, $created, $text, $password]);
    $cid = (int)$db->lastInsertId();
    $cids[] = $cid;
    $db->prepare("INSERT INTO {$p}relationships (cid,mid) VALUES (?,?)")->execute([$cid, $catIds[$cat]]);
    foreach (array_slice($tagIds, $i % 3, 2) as $mid) {
        $db->prepare("INSERT INTO {$p}relationships (cid,mid) VALUES (?,?)")->execute([$cid, $mid]);
    }
    // 评论
    $names = ['小明', '云间客', '路人甲', 'Alice'];
    $parent = 0;
    $n = 2 + ($i % 4);
    for ($k = 0; $k < $n; $k++) {
        $name = $names[$k % 4];
        $db->prepare("INSERT INTO {$p}comments (cid,created,author,authorId,ownerId,mail,url,ip,agent,text,type,status,parent) VALUES (?,?,?,?,1,?,?,'127.0.0.1','Mozilla/5.0 (Macintosh) Chrome/120',?,'comment','approved',?)")
            ->execute([$cid, $created + $k * 3600, $name, $k === 1 ? 1 : 0, strtolower($name) . '@example.com', 'https://example.com', "这是第 " . ($k + 1) . " 条测试评论，内容包含 **Markdown** 与 `代码`。", $k >= 2 ? $parent : 0]);
        if ($k === 0) $parent = (int)$db->lastInsertId();
    }
    $db->prepare("UPDATE {$p}contents SET commentsNum=? WHERE cid=?")->execute([$n, $cid]);
}
// 独立页
$pages = [
    ['归档', 'archives', 'page-archives.php', '这里是全部文章的归档。'],
    ['友链', 'links', 'page-links.php', '欢迎交换友链，请在评论区留下站点信息。'],
    ['关于', 'about', 'page-about.php', $lorem],
];
foreach ($pages as [$title, $slug, $tpl, $text]) {
    $db->prepare("INSERT INTO {$p}contents (title,slug,created,modified,text,\"order\",authorId,template,type,status,password,commentsNum,allowComment,allowPing,allowFeed,parent) VALUES (?,?,?,?,?,0,1,?,'page','publish',NULL,0,'1','1','1',0)")
        ->execute([$title, $slug, $now, $now, '<!--markdown-->' . $text, $tpl]);
    $cid = (int)$db->lastInsertId();
    if ($slug === 'links') {
        $json = json_encode([
            ['name' => 'Typecho', 'url' => 'https://typecho.org', 'avatar' => 'https://typecho.org/favicon.ico', 'desc' => '轻量开源博客程序'],
            ['name' => '云间客', 'url' => 'https://example.com', 'avatar' => '', 'desc' => '热爱生活，偶尔写代码'],
            ['name' => 'Nimbus', 'url' => 'https://github.com/underestimatedme/Nimbus', 'avatar' => 'https://invalid.invalid/x.png', 'desc' => '本主题仓库'],
        ], JSON_UNESCAPED_UNICODE);
        $db->prepare("INSERT INTO {$p}fields (cid,name,type,str_value,int_value,float_value) VALUES (?,'links','str',?,0,0)")->execute([$cid, $json]);
    }
}
// 标记
$db->prepare("INSERT INTO {$p}contents (title,slug,created,modified,text,\"order\",authorId,template,type,status,password,commentsNum,allowComment,allowPing,allowFeed,parent) VALUES ('seed-marker','nimbus-seed-marker',0,0,'',0,1,NULL,'post','hidden',NULL,0,'0','0','0',0)")->execute();
// 更新分类/标签计数
foreach ($db->query("SELECT mid FROM {$p}metas")->fetchAll(PDO::FETCH_COLUMN) as $mid) {
    $c = $db->query("SELECT COUNT(*) FROM {$p}relationships r JOIN {$p}contents c ON c.cid=r.cid WHERE r.mid=$mid AND c.status='publish'")->fetchColumn();
    $db->prepare("UPDATE {$p}metas SET count=? WHERE mid=?")->execute([$c, $mid]);
}
// 置顶与 Hero 配置示例
opt($db, $p, 'TopPosts', implode(',', array_slice($cids, 0, 3)));
opt($db, $p, 'HeroTitle', '给时光|以生命');
opt($db, $p, 'HeroSubtitles', "记录、思考、与世界温柔相处\n一个开源免费的 Typecho 主题");
opt($db, $p, 'HeroKicker', '今日天气 · 晴');
opt($db, $p, 'NoticeHtml', '主题 Nimbus 1.0 开源发布，欢迎 Star 与反馈 🎉');
opt($db, $p, 'SocialLinks', "GitHub|https://github.com|github\n微博|https://weibo.com|weibo\nBilibili|https://bilibili.com|bilibili\n邮箱|mailto:a@b.com|email");
opt($db, $p, 'RewardEnable', '1');
opt($db, $p, 'RewardAlipay', 'https://picsum.photos/id/1/300/300');
opt($db, $p, 'RewardWechat', 'https://picsum.photos/id/2/300/300');
opt($db, $p, 'SiteSince', '2024-01-01');
echo "seed: ok (" . count($cids) . " posts)\n";
