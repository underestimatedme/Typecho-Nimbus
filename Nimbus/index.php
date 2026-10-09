<?php
/**
 * Nimbus — 开源免费的 Typecho 主题
 *
 * @package Nimbus
 * @author Nimbus Contributors
 * @version 1.0.0
 * @link https://github.com/underestimatedme/Nimbus
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\Options;
View::partial('header', ['archive' => $this]);
?>
<div class="n-container">
  <div class="n-wrap" data-side="<?= View::e((string)Options::get('SidebarPosition', 'right')) ?>">
    <section class="n-main">
      <?php while ($this->next()): ?>
      <h2><a href="<?php $this->permalink(); ?>"><?php $this->title(); ?></a></h2>
      <?php endwhile; ?>
    </section>
    <aside class="n-side"></aside>
  </div>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
