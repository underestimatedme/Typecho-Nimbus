<?php
/**
 * 后台面板：管理 → Nimbus（说说 / 友链 / 相册 / 统计 / 邮件）
 * 由 admin/extending.php 载入，已有 $options、$user、$security、$request。
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

use TypechoPlugin\NimbusKit\Store;
use TypechoPlugin\NimbusKit\Config;
use TypechoPlugin\NimbusKit\Api;
use TypechoPlugin\NimbusKit\Plugin;

$user->pass('administrator');
$adminDir = __TYPECHO_ROOT_DIR__ . __TYPECHO_ADMIN_DIR__;
include $adminDir . 'header.php';
include $adminDir . 'menu.php';

$e = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
$tabs = ['moments' => '说说', 'links' => '友链', 'albums' => '相册', 'stats' => '统计', 'mail' => '邮件'];
$tab = (string)$request->get('tab', 'moments');
if (!isset($tabs[$tab])) $tab = 'moments';
$edit = (int)$request->get('edit');
$self = function (array $q = []) use ($options) {
    return \Typecho\Common::url('extending.php?panel=' . urlencode(Plugin::PANEL) . ($q ? '&' . http_build_query($q) : ''), (string)$options->adminUrl);
};
$act = function (string $do, array $q = []) use ($security) {
    return $security->getIndex('/action/nimbus-kit?' . http_build_query(array_merge(['do' => $do], $q)));
};
$pluginUrl = rtrim((string)$options->pluginUrl, '/') . '/NimbusKit';
?>
<link rel="stylesheet" href="<?= $e($pluginUrl) ?>/assets/admin.css?v=<?= Api::VERSION ?>">
<div class="main">
  <div class="body container">
    <?php include $adminDir . 'page-title.php'; ?>
    <div class="nk">
      <nav class="nk-tabs">
        <?php foreach ($tabs as $k => $label): ?><a class="<?= $k === $tab ? 'is-on' : '' ?>" href="<?= $e($self(['tab' => $k])) ?>"><?= $e($label) ?></a><?php endforeach; ?>
        <a class="nk-tabs-cfg" href="<?= $e(\Typecho\Common::url('options-plugin.php?config=NimbusKit', (string)$options->adminUrl)) ?>">插件设置</a>
      </nav>

<?php if ($tab === 'moments'):
    $page = max(1, (int)$request->get('page', 1));
    $data = Store::moments($page, 20, true);
    $cur = $edit ? Store::moment($edit) : null; ?>
      <div class="nk-grid">
        <form class="nk-card nk-form" method="post" action="<?= $e($act('moment-save')) ?>">
          <h3><?= $cur ? '编辑说说' : '发布说说' ?></h3>
          <?php if ($cur): ?><input type="hidden" name="id" value="<?= (int)$cur['id'] ?>"><?php endif; ?>
          <label>内容<textarea name="content" rows="6" placeholder="此刻的想法…（支持换行，链接会自动识别）"><?= $e($cur['raw'] ?? '') ?></textarea></label>
          <label>图片<textarea name="images" rows="3" placeholder="每行一个图片 URL"><?= $e(implode("\n", $cur['images'] ?? [])) ?></textarea></label>
          <label>状态<select name="status"><option value="publish"<?= ($cur['status'] ?? 'publish') === 'publish' ? ' selected' : '' ?>>公开</option><option value="hidden"<?= ($cur['status'] ?? '') === 'hidden' ? ' selected' : '' ?>>隐藏</option></select></label>
          <div class="nk-actions"><button class="btn primary" type="submit"><?= $cur ? '保存修改' : '发布' ?></button><?php if ($cur): ?><a class="btn" href="<?= $e($self(['tab' => 'moments'])) ?>">取消</a><?php endif; ?></div>
          <p class="nk-tip">前台展示：新建独立页面，模板选择「说说」。</p>
        </form>
        <div class="nk-card">
          <h3>全部说说 <small><?= $data['total'] ?> 条</small></h3>
          <?php if (empty($data['items'])): ?><p class="nk-empty">还没有说说。</p><?php endif; ?>
          <ul class="nk-list">
            <?php foreach ($data['items'] as $m): ?>
            <li>
              <div class="nk-list-main">
                <div class="nk-moment"><?= $m['content'] ?></div>
                <?php if ($m['images']): ?><div class="nk-thumbs"><?php foreach (array_slice($m['images'], 0, 6) as $img): ?><i style="background-image:url('<?= $e($img) ?>')"></i><?php endforeach; ?></div><?php endif; ?>
                <div class="nk-meta"><?= date('Y-m-d H:i', $m['created']) ?> · ♡ <?= (int)$m['likes'] ?><?= $m['status'] === 'hidden' ? ' · <b>隐藏</b>' : '' ?></div>
              </div>
              <div class="nk-ops">
                <a href="<?= $e($self(['tab' => 'moments', 'edit' => $m['id']])) ?>">编辑</a>
                <form method="post" action="<?= $e($act('moment-delete', ['id' => $m['id']])) ?>" onsubmit="return confirm('确定删除这条说说？')"><button type="submit">删除</button></form>
              </div>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php $pages = (int)ceil($data['total'] / 20); if ($pages > 1): ?>
          <div class="nk-pager"><?php for ($i = 1; $i <= $pages; $i++): ?><a class="<?= $i === $page ? 'is-on' : '' ?>" href="<?= $e($self(['tab' => 'moments', 'page' => $i])) ?>"><?= $i ?></a><?php endfor; ?></div>
          <?php endif; ?>
        </div>
      </div>

<?php elseif ($tab === 'links'):
    $links = Store::links(true);
    $cur = $edit ? Store::link($edit) : null; ?>
      <div class="nk-grid">
        <form class="nk-card nk-form" method="post" action="<?= $e($act('link-save')) ?>">
          <h3><?= $cur ? '编辑友链' : '添加友链' ?></h3>
          <?php if ($cur): ?><input type="hidden" name="id" value="<?= (int)$cur['id'] ?>"><?php endif; ?>
          <label>名称 *<input name="name" required maxlength="100" value="<?= $e($cur['name'] ?? '') ?>"></label>
          <label>网址 *<input name="url" required type="url" placeholder="https://" value="<?= $e($cur['url'] ?? '') ?>"></label>
          <label>头像 URL<input name="avatar" type="url" placeholder="https://（留空显示首字母）" value="<?= $e($cur['avatar'] ?? '') ?>"></label>
          <label>简介<input name="desc" maxlength="500" value="<?= $e($cur['desc'] ?? '') ?>"></label>
          <div class="nk-row">
            <label>排序<input name="sort" type="number" value="<?= (int)($cur['sort'] ?? 0) ?>"></label>
            <label>状态<select name="status"><option value="publish">显示</option><option value="hidden"<?= ($cur['status'] ?? '') === 'hidden' ? ' selected' : '' ?>>隐藏</option></select></label>
          </div>
          <div class="nk-actions"><button class="btn primary" type="submit">保存</button><?php if ($cur): ?><a class="btn" href="<?= $e($self(['tab' => 'links'])) ?>">取消</a><?php endif; ?></div>
          <p class="nk-tip">排序数字越小越靠前。这里有数据时，友链页优先使用这里的列表。</p>
        </form>
        <div class="nk-card">
          <h3>友链 <small><?= count($links) ?> 个</small></h3>
          <?php if (empty($links)): ?><p class="nk-empty">还没有友链。</p><?php endif; ?>
          <table class="nk-table">
            <thead><tr><th>排序</th><th>名称</th><th>网址</th><th>状态</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($links as $l): ?>
              <tr>
                <td><?= (int)$l['sort'] ?></td>
                <td><span class="nk-av"<?= $l['avatar'] ? ' style="background-image:url(\'' . $e($l['avatar']) . '\')"' : '' ?>></span><?= $e($l['name']) ?></td>
                <td><a href="<?= $e($l['url']) ?>" target="_blank" rel="noopener"><?= $e(preg_replace('#^https?://#', '', $l['url'])) ?></a></td>
                <td><?= $l['status'] === 'hidden' ? '隐藏' : '显示' ?></td>
                <td class="nk-ops"><a href="<?= $e($self(['tab' => 'links', 'edit' => $l['id']])) ?>">编辑</a><form method="post" action="<?= $e($act('link-delete', ['id' => $l['id']])) ?>" onsubmit="return confirm('确定删除？')"><button type="submit">删除</button></form></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

<?php elseif ($tab === 'albums'):
    $albums = Store::albums();
    $cur = $edit ? Store::album($edit) : null;
    $photoText = '';
    if ($cur) foreach ($cur['photos'] as $p) $photoText .= $p['url'] . ($p['caption'] !== '' ? '|' . $p['caption'] : '') . "\n"; ?>
      <div class="nk-grid">
        <form class="nk-card nk-form" method="post" action="<?= $e($act('album-save')) ?>">
          <h3><?= $cur ? '编辑相册' : '新建相册' ?></h3>
          <?php if ($cur): ?><input type="hidden" name="id" value="<?= (int)$cur['id'] ?>"><?php endif; ?>
          <label>标题 *<input name="title" required maxlength="200" value="<?= $e($cur['title'] ?? '') ?>"></label>
          <label>封面 URL<input name="cover" type="url" placeholder="留空使用第一张照片" value="<?= $e($cur['cover'] ?? '') ?>"></label>
          <label>简介<textarea name="description" rows="2"><?= $e($cur['description'] ?? '') ?></textarea></label>
          <label>照片<textarea name="photos" rows="8" placeholder="每行一张：图片URL|说明（说明可省略）"><?= $e($photoText) ?></textarea></label>
          <label>排序<input name="sort" type="number" value="<?= (int)($cur['sort'] ?? 0) ?>"></label>
          <div class="nk-actions"><button class="btn primary" type="submit">保存</button><?php if ($cur): ?><a class="btn" href="<?= $e($self(['tab' => 'albums'])) ?>">取消</a><?php endif; ?></div>
          <p class="nk-tip">前台展示：新建独立页面，模板选择「相册」。</p>
        </form>
        <div class="nk-card">
          <h3>相册 <small><?= count($albums) ?> 个</small></h3>
          <?php if (empty($albums)): ?><p class="nk-empty">还没有相册。</p><?php endif; ?>
          <div class="nk-albums">
            <?php foreach ($albums as $a): ?>
            <div class="nk-album">
              <i style="background-image:url('<?= $e($a['cover']) ?>')"></i>
              <b><?= $e($a['title']) ?></b><small><?= count($a['photos']) ?> 张 · 排序 <?= (int)$a['sort'] ?></small>
              <div class="nk-ops"><a href="<?= $e($self(['tab' => 'albums', 'edit' => $a['id']])) ?>">编辑</a><form method="post" action="<?= $e($act('album-delete', ['id' => $a['id']])) ?>" onsubmit="return confirm('确定删除这个相册？')"><button type="submit">删除</button></form></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

<?php elseif ($tab === 'stats'):
    $views = Store::topPosts('views', 20);
    $likes = Store::topPosts('likes', 20);
    $w = \Widget\Base\Contents::alloc();
    $link = function ($cid) use ($w) {
        $db = \Typecho\Db::get();
        $r = $db->fetchRow($db->select()->from('table.contents')->where('cid = ?', $cid)->limit(1));
        return $r ? $w->filter($r)['permalink'] : '#';
    }; ?>
      <div class="nk-grid nk-grid-2">
        <?php foreach (['浏览量 Top 20' => [$views, 'views'], '点赞 Top 20' => [$likes, 'likes']] as $title => $pair): ?>
        <div class="nk-card">
          <h3><?= $e($title) ?></h3>
          <?php if (empty($pair[0])): ?><p class="nk-empty">还没有数据。</p><?php endif; ?>
          <ol class="nk-rank">
            <?php foreach ($pair[0] as $r): ?><li><a href="<?= $e($link((int)$r['cid'])) ?>" target="_blank"><?= $e(htmlspecialchars_decode((string)$r['title'], ENT_QUOTES)) ?></a><b><?= (int)$r[$pair[1]] ?></b></li><?php endforeach; ?>
          </ol>
        </div>
        <?php endforeach; ?>
      </div>

<?php else: // mail
    $cfg = Config::all(); ?>
      <div class="nk-grid">
        <form class="nk-card nk-form" method="post" action="<?= $e($act('test-mail')) ?>">
          <h3>发送测试邮件</h3>
          <label>收件地址<input name="to" type="email" required value="<?= $e($cfg['adminMail'] ?: $user->mail) ?>"></label>
          <div class="nk-actions"><button class="btn primary" type="submit">发送</button></div>
          <p class="nk-tip">会同步连接 SMTP 服务器，失败时这里会显示具体原因。</p>
        </form>
        <div class="nk-card">
          <h3>当前配置</h3>
          <table class="nk-table nk-kv">
            <tr><th>邮件通知</th><td><?= $cfg['mailEnable'] === '1' ? '开启' : '关闭' ?></td></tr>
            <tr><th>SMTP</th><td><?= $e($cfg['smtpHost'] ?: '未设置') ?>:<?= $e($cfg['smtpPort']) ?>（<?= $e(['ssl' => 'SSL', 'tls' => 'STARTTLS', 'none' => '不加密'][$cfg['smtpSecure']] ?? $cfg['smtpSecure']) ?>）</td></tr>
            <tr><th>账号</th><td><?= $e($cfg['smtpUser'] ?: '未设置') ?></td></tr>
            <tr><th>发件人</th><td><?= $e(($cfg['mailFromName'] ?: (string)$options->title) . ' <' . ($cfg['mailFrom'] ?: $cfg['smtpUser']) . '>') ?></td></tr>
            <tr><th>通知站长</th><td><?= $cfg['notifyAdmin'] === '1' ? '是' : '否' ?></td></tr>
            <tr><th>通知被回复者</th><td><?= $cfg['notifyReply'] === '1' ? '是' : '否' ?></td></tr>
          </table>
          <p class="nk-tip"><a href="<?= $e(\Typecho\Common::url('options-plugin.php?config=NimbusKit', (string)$options->adminUrl)) ?>">修改邮件设置 →</a></p>
        </div>
      </div>
<?php endif; ?>
    </div>
  </div>
</div>
<?php
include $adminDir . 'copyright.php';
include $adminDir . 'common-js.php';
include $adminDir . 'footer.php';
