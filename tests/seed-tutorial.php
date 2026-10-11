<?php
/**
 * 开发站教程示例：一个 5 章的「Typecho 主题开发教程」分类 + 「教程」页面，并在主题设置里登记（幂等）。
 * 用法：php tests/seed-tutorial.php <sqlite文件>
 */
$db = new PDO('sqlite:' . $argv[1]);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$p = 'typecho_';
$slug = 'typecho-theme-tutorial';
$st = $db->prepare("SELECT mid FROM {$p}metas WHERE slug=? AND type='category'"); $st->execute([$slug]);
$mid = $st->fetchColumn();
if (!$mid) {
    $db->prepare("INSERT INTO {$p}metas (name,slug,type,description,count,\"order\",parent) VALUES (?,?,'category',?,0,9,0)")->execute(['Typecho 主题开发教程', $slug, '从零开始写一个现代化的 Typecho 主题：模板结构、样式系统、交互脚本到发布，一步一步来。']);
    $mid = (int)$db->lastInsertId();
}
$uid = (int)$db->query("SELECT uid FROM {$p}users ORDER BY uid LIMIT 1")->fetchColumn();
$chapters = [
    ['认识 Typecho 的主题结构', "这一章先看一个主题由哪些文件组成：index.php、post.php、functions.php 和若干部件。\n\n## 必须的文件\n\n每个主题至少需要 index.php，其余模板按需添加。\n\n## 主题信息\n\n在 index.php 顶部的注释里写主题名称、作者与版本。"],
    ['搭建本地开发环境', "用 PHP 内置服务器加 SQLite 就能跑起一个 Typecho 测试站。\n\n## 安装\n\n下载 Typecho，解压后运行安装程序。\n\n## 热更新\n\n主题目录用符号链接指向你的源码目录。"],
    ['用 CSS 变量做皮肤系统', "把颜色、圆角、阴影都定义成变量，一套结构可以换出多套皮肤。\n\n## 设计令牌\n\n先列出所有颜色与尺寸。\n\n## 深色模式\n\n用 data-theme 切换一组变量。"],
    ['无刷新页面切换', "用 fetch 加载下一页，替换主体内容，再配合 View Transitions 做过渡动画。\n\n## 拦截链接\n\n只处理站内链接。\n\n## 重新初始化\n\n页面切换后重新绑定脚本。"],
    ['打包与发布', "最后整理文件、写好 README，把主题发布出去。\n\n## 检查清单\n\nPHP 7.4 兼容、三种数据库测试。\n\n## 开源许可\n\n推荐使用 MIT。"],
];
$base = strtotime('2026-09-01 10:00:00');
foreach ($chapters as $i => [$title, $text]) {
    $cslug = $slug . '-' . ($i + 1);
    $st = $db->prepare("SELECT cid FROM {$p}contents WHERE slug=?"); $st->execute([$cslug]);
    $cid = $st->fetchColumn();
    if (!$cid) {
        $t = $base + $i * 3 * 86400;
        $db->prepare("INSERT INTO {$p}contents (title,slug,created,modified,text,\"order\",authorId,template,type,status,password,commentsNum,allowComment,allowPing,allowFeed,parent) VALUES (?,?,?,?,?,0,?,NULL,'post','publish',NULL,0,'1','1','1',0)")
            ->execute([$title, $cslug, $t, $t, "<!--markdown-->" . $text, $uid]);
        $cid = (int)$db->lastInsertId();
        $db->prepare("INSERT INTO {$p}relationships (cid,mid) VALUES (?,?)")->execute([$cid, $mid]);
        $db->prepare("INSERT INTO {$p}fields (cid,name,type,str_value,int_value,float_value) VALUES (?,'chapter','int',NULL,?,0)")->execute([$cid, $i + 1]);
    }
}
$db->prepare("UPDATE {$p}metas SET count=(SELECT COUNT(*) FROM {$p}relationships WHERE mid=?) WHERE mid=?")->execute([$mid, $mid]);
// 「教程」页面
$st = $db->prepare("SELECT cid FROM {$p}contents WHERE slug='tutorials'"); $st->execute();
if (!$st->fetchColumn()) {
    $t = time() - 3600;
    $db->prepare("INSERT INTO {$p}contents (title,slug,created,modified,text,\"order\",authorId,template,type,status,allowComment,allowPing,allowFeed,parent) VALUES ('教程','tutorials',?,?,'<!--markdown-->',12,?,'page-tutorials.php','page','publish','0','1','1',0)")->execute([$t, $t, $uid]);
}
echo "seed-tutorial: ok (mid $mid)\n";
