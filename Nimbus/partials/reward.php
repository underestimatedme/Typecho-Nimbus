<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\Options;
$codes = [];
foreach (['RewardWechat' => '微信', 'RewardAlipay' => '支付宝', 'RewardQQ' => 'QQ'] as $k => $label) {
    $u = (string)Options::get($k, '');
    if ($u !== '') $codes[] = ['label' => $label, 'url' => $u, 'key' => strtolower(substr($k, 6))];
}
if (empty($codes)) return;
?>
<dialog class="n-modal n-reward" data-reward aria-label="打赏">
  <div class="n-modal-in n-reward-in">
    <button type="button" class="n-icon-btn n-reward-close" data-reward-close aria-label="关闭"><?= View::icon('close') ?></button>
    <h3>请我喝杯咖啡 ☕</h3>
    <p>如果这篇文章对你有帮助，欢迎打赏支持。</p>
    <div class="n-reward-tabs" role="tablist">
      <?php foreach ($codes as $i => $c): ?><button type="button" role="tab" class="<?= $i === 0 ? 'is-on' : '' ?>" data-reward-tab="<?= $c['key'] ?>"><?= View::e($c['label']) ?></button><?php endforeach; ?>
    </div>
    <div class="n-reward-codes">
      <?php foreach ($codes as $i => $c): ?><figure class="<?= $i === 0 ? 'is-on' : '' ?>" data-reward-code="<?= $c['key'] ?>"><img src="<?= View::e($c['url']) ?>" alt="<?= View::e($c['label']) ?>收款码" loading="lazy"><figcaption><?= View::e($c['label']) ?>扫码</figcaption></figure><?php endforeach; ?>
    </div>
  </div>
</dialog>
