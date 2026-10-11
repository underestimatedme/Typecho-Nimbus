<?php
/** 杂志风首页的教程区块。变量：$archive */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Tutorial;
use Nimbus\Widgets;
$list = Tutorial::all($archive);
if (!$list) return;
$all = Widgets::pageUrl($archive, 'page-tutorials.php');
?>
<section class="n-home-tuts">
  <div class="n-section-head">
    <span class="n-section-eyebrow"><?= I18n::e('系列教程') ?></span>
    <h2><?= I18n::e('跟着教程，一步一步来') ?></h2>
    <?php if ($all !== ''): ?><a class="n-section-more" href="<?= View::e($all) ?>"><?= I18n::e('全部教程') ?><?= View::icon('arrow-right') ?></a><?php endif; ?>
  </div>
  <div class="n-tut-grid<?= count($list) === 1 ? ' is-single' : '' ?>">
    <?php foreach (array_slice($list, 0, 3) as $t) View::partial('tutorial-card', ['t' => $t]); ?>
  </div>
</section>
