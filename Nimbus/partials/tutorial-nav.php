<?php
/** 教程章节导航（文章页正文上方，可折叠）。变量：$tut（Tutorial::forPost 结果） */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Tutorial;
$t = $tut['tutorial']; $cur = $tut['index']; $n = count($t['chapters']);
?>
<details class="n-tut-nav" data-tutorial="<?= View::e(Tutorial::clientData($t)) ?>" data-tut-current="<?= (int)$t['chapters'][$cur]['cid'] ?>">
  <summary>
    <span class="n-tut-nav-icon"><?= View::icon('list') ?></span>
    <span class="n-tut-nav-txt"><small><?= I18n::e('系列教程') ?></small><b><?= View::e($t['name']) ?></b></span>
    <span class="n-tut-nav-pos"><?= I18n::e('第 {i} / {n} 章', ['i' => $cur + 1, 'n' => $n]) ?></span>
    <span class="n-tut-nav-caret"><?= View::icon('chevron-down') ?></span>
  </summary>
  <ol class="n-tut-list n-tut-list-sm">
    <?php foreach ($t['chapters'] as $i => $c): ?>
    <li data-tut-cid="<?= (int)$c['cid'] ?>"<?= $i === $cur ? ' class="is-current"' : '' ?>><a href="<?= View::e($c['url']) ?>"<?= $i === $cur ? ' aria-current="page"' : '' ?>><span class="n-tut-no"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span><span class="n-tut-txt"><b><?= View::e($c['title']) ?></b></span><span class="n-tut-check" aria-hidden="true"><?= View::icon('check') ?></span></a></li>
    <?php endforeach; ?>
  </ol>
  <a class="n-tut-nav-all" href="<?= View::e($t['url']) ?>"><?= I18n::e('查看教程目录') ?><?= View::icon('arrow-right') ?></a>
</details>
