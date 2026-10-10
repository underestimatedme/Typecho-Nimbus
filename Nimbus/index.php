<?php
/**
 * Nimbus — 开源免费的 Typecho 主题
 *
 * @package Nimbus
 * @author Nimbus Contributors
 * @version 1.1.0
 * @link https://github.com/underestimatedme/Nimbus
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\Options;
$isFront = $this->is('index') && (int)$this->request->get('page', 1) <= 1;
View::partial('header', ['archive' => $this]);
if ($isFront && Options::bool('HeroEnable')) View::partial('hero', ['archive' => $this]);
?>
<div class="n-container">
  <div class="n-wrap" data-side="<?= View::e((string)Options::get('SidebarPosition', 'right')) ?>">
    <section class="n-main">
      <?php if (!$this->is('index')) View::partial('list-head', ['archive' => $this]); ?>
      <?php if ($isFront) View::partial('top-carousel', ['archive' => $this]); ?>
      <?php View::partial('post-list', ['archive' => $this]); ?>
      <?php View::partial('pagination', ['archive' => $this]); ?>
    </section>
    <?php View::partial('sidebar', ['archive' => $this]); ?>
  </div>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
