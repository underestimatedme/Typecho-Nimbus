<?php
/** 临时列表（Task 6 替换） */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
?>
<div class="n-list" id="n-list" data-list>
<?php while ($archive->next()): ?>
<h2><a href="<?php $archive->permalink(); ?>"><?php $archive->title(); ?></a></h2>
<?php endwhile; ?>
</div>
