<?php
/** 教程目录页（教程分类的归档页）。变量：$archive, $t */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Tutorial;
$n = count($t['chapters']);
?>
<section class="n-tut-page" data-tutorial="<?= View::e(Tutorial::clientData($t)) ?>">
  <header class="n-tut-hero n-reveal">
    <div class="n-tut-hero-cover" style="background-image:url('<?= View::e($t['cover']) ?>')"></div>
    <div class="n-tut-hero-body">
      <span class="n-tut-eyebrow"><?= View::icon('list') ?><?= I18n::e('系列教程') ?></span>
      <h1><?= View::e($t['name']) ?></h1>
      <?php if ($t['description'] !== ''): ?><p><?= View::e($t['description']) ?></p><?php endif; ?>
      <div class="n-tut-meta"><span><b><?= $n ?></b><?= I18n::e('章') ?></span><span><b><?= (int)$t['minutes'] ?></b><?= I18n::e('分钟') ?></span><span><?= I18n::e('更新于 {d}', ['d' => View::date('Y-m-d', (int)$t['updated'])]) ?></span></div>
      <div class="n-tut-progress"><i data-tut-bar></i></div>
      <div class="n-tut-foot">
        <span class="n-tut-done" data-tut-done><?= I18n::e('还没开始') ?></span>
        <a class="n-btn n-btn-primary n-tut-go" href="<?= View::e($t['chapters'][0]['url']) ?>" data-tut-go><?= I18n::e('开始学习') ?><?= View::icon('arrow-right') ?></a>
      </div>
    </div>
  </header>
  <ol class="n-tut-list">
    <?php foreach ($t['chapters'] as $i => $c): ?>
    <li class="n-reveal" data-tut-cid="<?= (int)$c['cid'] ?>">
      <a href="<?= View::e($c['url']) ?>">
        <span class="n-tut-no"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
        <span class="n-tut-txt"><b><?= View::e($c['title']) ?></b><?php if ($c['excerpt'] !== ''): ?><em><?= View::e($c['excerpt']) ?></em><?php endif; ?></span>
        <span class="n-tut-time"><?= I18n::e('{n} 分钟', ['n' => $c['minutes']]) ?></span>
        <span class="n-tut-check" aria-hidden="true"><?= View::icon('check') ?></span>
      </a>
    </li>
    <?php endforeach; ?>
  </ol>
</section>
