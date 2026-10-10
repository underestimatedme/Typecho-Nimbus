<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Options;
$codes = [];
foreach (['RewardWechat' => I18n::t('微信'), 'RewardAlipay' => I18n::t('支付宝'), 'RewardQQ' => 'QQ'] as $k => $label) {
    $u = (string)Options::get($k, '');
    if ($u !== '') $codes[] = ['label' => $label, 'url' => $u, 'key' => strtolower(substr($k, 6))];
}
if (empty($codes)) return;
?>
<dialog class="n-modal n-reward" data-reward aria-label="<?= I18n::e('打赏') ?>">
  <div class="n-modal-in n-reward-in">
    <button type="button" class="n-icon-btn n-reward-close" data-reward-close aria-label="<?= I18n::e('关闭') ?>"><?= View::icon('close') ?></button>
    <h3><?= I18n::e('请我喝杯咖啡 ☕') ?></h3>
    <p><?= I18n::e('如果这篇文章对你有帮助，欢迎打赏支持。') ?></p>
    <div class="n-reward-tabs" role="tablist">
      <?php foreach ($codes as $i => $c): ?><button type="button" role="tab" class="<?= $i === 0 ? 'is-on' : '' ?>" data-reward-tab="<?= $c['key'] ?>"><?= View::e($c['label']) ?></button><?php endforeach; ?>
    </div>
    <div class="n-reward-codes">
      <?php foreach ($codes as $i => $c): ?><figure class="<?= $i === 0 ? 'is-on' : '' ?>" data-reward-code="<?= $c['key'] ?>"><img src="<?= View::e($c['url']) ?>" alt="<?= I18n::e('{name}收款码', ['name' => $c['label']]) ?>" loading="lazy"><figcaption><?= I18n::e('{name}扫码', ['name' => $c['label']]) ?></figcaption></figure><?php endforeach; ?>
    </div>
  </div>
</dialog>
