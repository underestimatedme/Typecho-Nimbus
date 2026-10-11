<?php
/**
 * 教程
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Tutorial;

$list = Tutorial::all($this);
View::partial('header', ['archive' => $this]);
?>
<div class="n-container">
  <section class="n-tuts-page">
    <header class="n-list-head n-reveal"><div class="n-kicker"><?= View::icon('list') ?> <?= I18n::e('系列教程') ?></div><h1><?= View::e(View::t($this->title)) ?></h1><p><?= $list ? I18n::e('{n} 个教程', ['n' => count($list)]) : I18n::e('跟着教程，一步一步来') ?></p></header>
    <?php if (!$list): ?>
      <div class="n-empty"><?= View::icon('info') ?><p><?= I18n::e('还没有教程。在「主题设置 → 首页 → 教程分类」中填写分类缩略名即可。') ?></p></div>
    <?php else: ?>
    <div class="n-tut-grid"><?php foreach ($list as $t) View::partial('tutorial-card', ['t' => $t]); ?></div>
    <?php endif; ?>
  </section>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
