<?php
namespace Nimbus;

/** Cuteen 闪念：原页面已审核的顶层评论，直接读取，不迁移、不依赖插件。 */
final class Soliloquize
{
    public static function data($archive, int $page, int $size): array
    {
        if ($archive->hidden) return ['items' => [], 'total' => 0];
        $db = \Typecho_Db::get();
        $base = function ($query) use ($archive) {
            return $query->from('table.comments')
                ->where('cid = ?', (int)$archive->cid)
                ->where('status = ? AND type = ? AND parent = ?', 'approved', 'comment', 0);
        };
        $count = $db->fetchRow($base($db->select(['COUNT(coid)' => 'n'])));
        $size = max(1, min(100, $size));
        $rows = $db->fetchAll($base($db->select())
            ->order('created', \Typecho_Db::SORT_DESC)->order('coid', \Typecho_Db::SORT_DESC)
            ->page(max(1, $page), $size));
        $renderer = \Typecho_Widget::widget('Widget_Abstract_Comments@nimbus-soliloquize');
        $items = [];
        foreach ($rows as $row) {
            $renderer->push($row);
            $content = Content::renderFrom((string)$renderer->content, ['lightbox' => Options::bool('LightboxEnable'), 'host' => (string)parse_url((string)\Helper::options()->siteUrl, PHP_URL_HOST)]);
            $items[] = ['id' => (int)$row['coid'], 'created' => (int)$row['created'], 'author' => (string)$row['author'], 'mail' => (string)$row['mail'], 'content' => $content['html']];
        }
        return ['items' => $items, 'total' => (int)($count['n'] ?? 0)];
    }
}
