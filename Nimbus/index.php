<?php
/**
 * Nimbus — 开源免费的 Typecho 主题
 *
 * @package Nimbus
 * @author Nimbus Contributors
 * @version 0.1
 * @link https://github.com/underestimatedme/Typecho-Nimbus
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\Options;
$isFront = $this->is('index') && (int)$this->request->get('page', 1) <= 1;
// 杂志风首页：封面 + Bento + 通栏文章网格（第二页起与其他列表页一样带侧栏）
$magazine = $isFront && (string)Options::get('HomeStyle', 'magazine') === 'magazine';
View::partial('header', ['archive' => $this]);
if ($magazine) View::partial('home-cover', ['archive' => $this]);
elseif ($isFront && Options::bool('HeroEnable')) View::partial('hero', ['archive' => $this]);
?>
<div class="n-container">
  <?php if ($magazine) { View::partial('home-bento', ['archive' => $this]); View::partial('home-tutorials', ['archive' => $this]); View::partial('home-posts-head', ['archive' => $this]); } ?>
  <?php
  // 教程分类：显示课程目录而不是普通文章列表
  $nimbusTut = $this->is('category') ? \Nimbus\Tutorial::get($this, (string)$this->getArchiveSlug()) : null;
  if ($nimbusTut):
      View::partial('tutorial-outline', ['archive' => $this, 't' => $nimbusTut]);
  else:
  ?>
  <div class="n-wrap<?= $magazine ? ' n-wrap-magazine' : '' ?>" data-side="<?= $magazine ? 'none' : View::e((string)Options::get('SidebarPosition', 'right')) ?>">
    <section class="n-main">
      <?php if (!$this->is('index')) View::partial('list-head', ['archive' => $this]); ?>
      <?php if ($isFront && !$magazine) View::partial('top-carousel', ['archive' => $this]); ?>
      <?php View::partial('post-list', ['archive' => $this]); ?>
      <?php View::partial('pagination', ['archive' => $this]); ?>
    </section>
    <?php if (!$magazine) View::partial('sidebar', ['archive' => $this]); ?>
  </div>
  <?php endif; ?>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
