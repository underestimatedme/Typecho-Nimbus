<?php
/** 杂志风首页「最新文章」标题。变量：$archive */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\Options;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Widgets;
?>
<div class="n-section-head" id="n-posts">
  <span class="n-section-eyebrow"><?= I18n::e('最新文章') ?></span>
  <h2><?= I18n::e('慢慢写，慢慢读') ?></h2>
  <?php if (Options::bool('PaginationSwitch') && $archive->getTotal() > (int)\Helper::options()->pageSize) View::partial('list-mode', ['archive' => $archive]); ?>
  <?php $arch = Widgets::pageUrl($archive, 'page-archives.php'); if ($arch !== ''): ?><a class="n-section-more" href="<?= View::e($arch) ?>"><?= I18n::e('全部归档') ?><?= View::icon('arrow-right') ?></a><?php endif; ?>
</div>
