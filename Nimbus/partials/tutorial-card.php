<?php
/** 教程卡片（首页、教程页共用）。变量：$t（Tutorial::all() 的一项） */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Tutorial;
$n = count($t['chapters']);
?>
<article class="n-tut-card n-reveal" data-tutorial="<?= View::e(Tutorial::clientData($t)) ?>">
  <a class="n-tut-cover" href="<?= View::e($t['url']) ?>" style="background-image:url('<?= View::e($t['cover']) ?>')">
    <span class="n-tut-badge"><?= View::icon('list') ?><?= I18n::e('{n} 章', ['n' => $n]) ?></span>
  </a>
  <div class="n-tut-body">
    <span class="n-tut-eyebrow"><?= I18n::e('系列教程') ?></span>
    <h3><a href="<?= View::e($t['url']) ?>"><?= View::e($t['name']) ?></a></h3>
    <?php if ($t['description'] !== ''): ?><p><?= View::e($t['description']) ?></p><?php endif; ?>
    <div class="n-tut-meta"><span><?= View::icon('clock') ?><?= I18n::e('约 {n} 分钟', ['n' => $t['minutes']]) ?></span><span><?= View::icon('calendar') ?><?= I18n::e('更新于 {d}', ['d' => View::date('Y-m-d', (int)$t['updated'])]) ?></span></div>
    <div class="n-tut-progress" aria-hidden="true"><i data-tut-bar></i></div>
    <div class="n-tut-foot">
      <span class="n-tut-done" data-tut-done><?= I18n::e('还没开始') ?></span>
      <a class="n-btn n-btn-primary n-tut-go" href="<?= View::e($t['chapters'][0]['url']) ?>" data-tut-go><?= I18n::e('开始学习') ?><?= View::icon('arrow-right') ?></a>
    </div>
  </div>
</article>
