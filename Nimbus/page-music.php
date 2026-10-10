<?php
/**
 * 音乐
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;

$music = \Nimbus\Music::config();
View::partial('header', ['archive' => $this]);
?>
<div class="n-container">
  <section class="n-music-page" data-music-page>
    <header class="n-list-head n-reveal"><div class="n-kicker"><?= View::icon('music') ?> 音乐</div><h1><?= View::e(View::t($this->title)) ?></h1><?php if ($this->fields->subtitle): ?><p><?= View::e((string)$this->fields->subtitle) ?></p><?php endif; ?></header>
    <?php if ($music === null): ?>
      <div class="n-empty"><?= View::icon('info') ?><p>还没有开启音乐播放器。请在「主题设置 → 媒体」中开启并填写歌单。</p></div>
    <?php else: ?>
    <div class="n-mp">
      <div class="n-mp-stage">
        <div class="n-mp-vinyl">
          <div class="n-mp-disc"><i class="n-mp-cover" data-mp-cover></i></div>
          <span class="n-mp-arm" aria-hidden="true"></span>
        </div>
        <canvas class="n-mp-viz" data-mp-viz aria-hidden="true"></canvas>
      </div>
      <div class="n-mp-side">
        <div class="n-mp-meta"><h2 data-mp-title>加载中…</h2><p data-mp-artist></p></div>
        <div class="n-mp-lrc" data-mp-lrc aria-live="off"></div>
        <div class="n-mp-seekrow">
          <span data-mp-cur>0:00</span>
          <input type="range" min="0" max="1000" value="0" data-mp-seek aria-label="播放进度">
          <span data-mp-dur>0:00</span>
        </div>
        <div class="n-mp-ctrl">
          <button type="button" data-mp-prev aria-label="上一首"><?= View::icon('skip-back') ?></button>
          <button type="button" class="n-mp-main" data-mp-toggle aria-label="播放 / 暂停"><?= View::icon('play', 'n-ico-play') ?><?= View::icon('pause', 'n-ico-pause') ?></button>
          <button type="button" data-mp-next aria-label="下一首"><?= View::icon('skip-forward') ?></button>
          <label class="n-mp-vol"><?= View::icon('volume') ?><input type="range" min="0" max="100" value="80" data-mp-vol aria-label="音量"></label>
        </div>
      </div>
    </div>
    <ol class="n-mp-list" data-mp-list></ol>
    <?php endif; ?>
  </section>
  <?php if ($this->allow('comment')) $this->need('comments.php'); ?>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
