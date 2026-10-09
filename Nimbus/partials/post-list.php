<?php
/** 文章列表容器：按 ListStyle 选择卡片片段。变量：$archive */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\Options;
use Nimbus\View;
$style = (string)Options::get('ListStyle', 'card');
if (!in_array($style, ['card', 'grid', 'plain'], true)) $style = 'card';
$partial = 'post-' . $style;
?>
<div class="n-list n-list-<?= $style ?>" id="n-list" data-list data-style="<?= $style ?>">
<?php if ($archive->have()): $i = 0; while ($archive->next()): $i++; ?>
<?php View::partial($partial, ['archive' => $archive, 'post' => $archive, 'index' => $i]); ?>
<?php endwhile; else: ?>
<div class="n-empty"><?= View::icon('info') ?><p>这里还没有文章。</p></div>
<?php endif; ?>
</div>
