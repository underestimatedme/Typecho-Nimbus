<?php
/** 搜索弹窗 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
$site = (string)\Helper::options()->siteUrl;
?>
<dialog class="n-modal n-search" id="n-search" data-search aria-label="<?= I18n::e('搜索') ?>">
  <div class="n-modal-in">
    <form class="n-search-form" action="<?= View::e($site) ?>" method="get" data-search-form>
      <?= View::icon('search', 'n-search-ico') ?>
      <input class="n-search-input" type="search" name="s" placeholder="<?= I18n::e('搜索文章…') ?>" autocomplete="off" required data-search-input>
      <button type="button" class="n-icon-btn n-search-close" data-search-close aria-label="<?= I18n::e('关闭') ?>"><?= View::icon('close') ?></button>
    </form>
    <div class="n-search-results" data-search-results hidden></div>
    <div class="n-search-hint"><span><kbd>Enter</kbd> <?= I18n::e('搜索') ?></span><span><kbd>Esc</kbd> <?= I18n::e('关闭') ?></span></div>
  </div>
</dialog>
