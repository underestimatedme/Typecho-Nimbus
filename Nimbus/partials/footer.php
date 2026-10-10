<?php
/** 页脚：</main>、页脚内容、返回顶部、脚本。变量：$archive */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\Options;
use Nimbus\View;
$o = \Helper::options();
$icp = (string)Options::get('ICP', '');
$police = (string)Options::get('Police', '');
$policeCode = preg_replace('/\D+/', '', $police);
?>
</main>
<footer class="n-footer" id="n-footer">
  <div class="n-container n-footer-in">
    <div class="n-footer-brand"><i class="n-logo-mark" aria-hidden="true"></i><span><?= View::e((string)$o->title) ?></span></div>
    <div class="n-footer-meta">
      <span>© <?= date('Y') ?> <?= View::e((string)$o->title) ?></span>
      <?php if ($icp !== ''): ?><a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener nofollow"><?= View::e($icp) ?></a><?php endif; ?>
      <?php if ($police !== ''): ?><a href="http://www.beian.gov.cn/portal/registerSystemInfo?recordcode=<?= View::e($policeCode) ?>" target="_blank" rel="noopener nofollow"><?= View::e($police) ?></a><?php endif; ?>
      <span>Powered by <a href="https://typecho.org" target="_blank" rel="noopener">Typecho</a> · Theme <a href="https://github.com/underestimatedme/Nimbus" target="_blank" rel="noopener">Nimbus</a></span>
    </div>
    <?php if (Options::get('FooterHtml')): ?><div class="n-footer-custom"><?= (string)Options::get('FooterHtml') ?></div><?php endif; ?>
  </div>
</footer>
<button type="button" class="n-totop" id="n-totop" data-totop aria-label="返回顶部">
  <svg class="n-totop-ring" viewBox="0 0 50 50" aria-hidden="true"><circle class="n-totop-bg" cx="25" cy="25" r="23.5"/><circle class="n-totop-fg" cx="25" cy="25" r="23.5"/></svg>
  <?= View::icon('arrow-up') ?>
</button>
<?php View::partial('search-modal', ['archive' => $archive]); ?>
<?php if (\Nimbus\Music::config() !== null) View::partial('player', ['archive' => $archive]); ?>
<?= (string)Options::get('FootCode', '') ?>
<?php if (Options::get('CustomJs')): ?><script><?= (string)Options::get('CustomJs') ?></script>
<?php endif; ?>
<script type="module" src="<?= View::e(View::asset('js/app.js')) ?>"></script>
<?php $archive->footer(); ?>
</body>
</html>
