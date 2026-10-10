<?php
/**
 * 留言板
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;

View::partial('header', ['archive' => $this]);
?>
<div class="n-container">
  <div class="n-wrap n-wrap-full" data-side="none">
    <section class="n-main n-messages-page">
      <header class="n-list-head n-reveal"><div class="n-kicker"><?= View::icon('comment') ?> <?= I18n::e('留言板') ?></div><h1><?= View::e(View::t($this->title)) ?></h1><p><?= I18n::e('{n} 条留言', ['n' => (int)$this->commentsNum]) ?></p></header>
      <?php if (trim(strip_tags((string)$this->content)) !== ''): ?><div class="n-prose n-reveal n-messages-intro" id="n-prose"><?= $this->content ?></div><?php endif; ?>
      <?php View::$commentLayout = 'wall'; $this->need('comments.php'); View::$commentLayout = 'thread'; ?>
    </section>
  </div>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
