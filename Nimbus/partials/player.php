<?php
/** 悬浮音乐播放器骨架（内容由 player.js 填充；位于 #n-main 之外，切换页面不中断） */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
?>
<div class="n-player" id="n-player" data-player hidden>
  <button type="button" class="n-player-disc" data-pl-toggle aria-label="<?= I18n::e('播放 / 暂停') ?>">
    <svg class="n-player-ring" viewBox="0 0 60 60" aria-hidden="true"><circle class="n-player-ring-bg" cx="30" cy="30" r="27"/><circle class="n-player-ring-fg" cx="30" cy="30" r="27" data-pl-ring/></svg>
    <i class="n-player-cover" data-pl-cover></i>
    <span class="n-player-state"><?= View::icon('play', 'n-ico-play') ?><?= View::icon('pause', 'n-ico-pause') ?></span>
  </button>
  <div class="n-player-panel">
    <div class="n-player-info"><b data-pl-title>—</b><em data-pl-artist></em></div>
    <div class="n-player-ctrl">
      <button type="button" data-pl-prev aria-label="<?= I18n::e('上一首') ?>"><?= View::icon('skip-back') ?></button>
      <button type="button" data-pl-toggle aria-label="<?= I18n::e('播放 / 暂停') ?>"><?= View::icon('play', 'n-ico-play') ?><?= View::icon('pause', 'n-ico-pause') ?></button>
      <button type="button" data-pl-next aria-label="<?= I18n::e('下一首') ?>"><?= View::icon('skip-forward') ?></button>
      <button type="button" data-pl-list aria-label="<?= I18n::e('歌单') ?>"><?= View::icon('list') ?></button>
    </div>
    <div class="n-player-bar" data-pl-seek><i data-pl-bar></i></div>
  </div>
  <div class="n-player-list" data-pl-listbox hidden><ol data-pl-items></ol></div>
</div>
