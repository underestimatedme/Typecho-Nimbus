<?php
namespace TypechoPlugin\NimbusKit;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Text as TextInput;
use Typecho\Widget\Helper\Form\Element\Password;
use Typecho\Widget\Helper\Form\Element\Radio;
use Typecho\Widget\Helper\Form\Element\Checkbox;
use Utils\Helper;

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Nimbus 主题配套插件：浏览量、点赞、即时搜索、AJAX 评论、邮件通知、说说、友链、相册、Sitemap
 *
 * @package NimbusKit
 * @author Nimbus Contributors
 * @version 1.1.0
 * @link https://github.com/underestimatedme/Nimbus
 */
class Plugin implements PluginInterface
{
    const PANEL = 'NimbusKit/panel.php';

    public static function activate()
    {
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            throw new \Typecho\Plugin\Exception(_t('NimbusKit 需要 PHP 7.4 或更高版本'));
        }
        Store::install();
        Helper::addRoute('nimbus_api', '/nimbus-api/[endpoint]', Action::class, 'dispatch', 'index');
        Helper::addRoute('nimbus_sitemap', '/sitemap.xml', Action::class, 'sitemap', 'index');
        Helper::addAction('nimbus-kit', AdminAction::class);
        Helper::addPanel(3, self::PANEL, 'Nimbus', '说说、友链、相册与统计', 'administrator');
        \Typecho\Plugin::factory('Widget_Feedback')->finishComment = Hooks::class . '::finishComment';
        // AJAX 响应放在最后（权重 99），保证其他插件的 finishComment 钩子都已执行
        \Typecho\Plugin::factory('Widget_Feedback')->finishComment_99 = Hooks::class . '::ajaxRespond';
        \Typecho\Plugin::factory('Widget_Comments_Edit')->finishComment = Hooks::class . '::finishComment';
        \Typecho\Plugin::factory('Widget_Comments_Edit')->mark = Hooks::class . '::mark';
        return _t('NimbusKit 已启用：数据表已就绪，可在「管理 → Nimbus」中管理说说、友链与相册。');
    }

    public static function deactivate()
    {
        Helper::removeRoute('nimbus_api');
        Helper::removeRoute('nimbus_sitemap');
        Helper::removeAction('nimbus-kit');
        Helper::removePanel(3, self::PANEL);
        if (Config::get('dropOnDeactivate') === '1') {
            Store::uninstall();
            self::backup(null);
        } else {
            // Typecho 禁用插件时会删除插件配置；先另存，重新启用时恢复（SMTP 等设置不丢）
            self::backup(Config::all());
        }
    }

    const BACKUP = 'nimbuskit:config-backup';

    /** 写入或清除配置备份（存放在 options 表）。 */
    private static function backup(?array $cfg): void
    {
        $db = \Typecho\Db::get();
        $db->query($db->delete('table.options')->where('name = ? AND user = 0', self::BACKUP));
        if ($cfg !== null) {
            $db->query($db->insert('table.options')->rows(['name' => self::BACKUP, 'user' => 0, 'value' => serialize($cfg)]));
        }
    }

    private static function restore(): ?array
    {
        $db = \Typecho\Db::get();
        $row = $db->fetchRow($db->select('value')->from('table.options')->where('name = ? AND user = 0', self::BACKUP)->limit(1));
        if (!$row) return null;
        $v = @unserialize((string)$row['value'], ['allowed_classes' => false]);
        return is_array($v) ? $v : null;
    }

    public static function config(Form $form)
    {
        echo '<div style="margin:0 0 16px;padding:12px 16px;border-radius:10px;background:#eff6ff;color:#1e3a5f;line-height:1.7">'
            . 'NimbusKit v' . Api::VERSION . '。说说、友链、相册与统计在「管理 → Nimbus」中管理，测试邮件也在那里发送。</div>';

        $f = new Checkbox('features', [
            'views' => '浏览量统计', 'likes' => '点赞', 'search' => '即时搜索',
            'ajaxComment' => 'AJAX 评论（不刷新页面）', 'sitemap' => 'Sitemap（/sitemap.xml）',
        ], Config::FEATURES, _t('启用的功能'), _t('关闭后主题会自动隐藏对应的按钮与数据。'));
        $form->addInput($f);

        $form->addInput(new Radio('mailEnable', ['1' => '开启', '0' => '关闭'], '0', _t('邮件通知'), _t('新评论通知站长、回复通知被回复者。')));
        $form->addInput(new TextInput('smtpHost', null, '', _t('SMTP 服务器'), _t('如 smtp.qq.com、smtp.163.com、smtp.gmail.com')));
        $form->addInput(new TextInput('smtpPort', null, '465', _t('端口'), _t('SSL 一般为 465，STARTTLS 一般为 587，明文为 25。')));
        $form->addInput(new Radio('smtpSecure', ['ssl' => 'SSL', 'tls' => 'STARTTLS', 'none' => '不加密'], 'ssl', _t('加密方式')));
        $form->addInput(new TextInput('smtpUser', null, '', _t('SMTP 用户名'), _t('通常是完整邮箱地址。')));
        $form->addInput(new Password('smtpPass', null, '', _t('SMTP 密码 / 授权码'), _t('QQ、163 等邮箱需要在邮箱设置里生成授权码。清空并保存即可删除已保存的密码。')));
        $form->addInput(new TextInput('mailFromName', null, '', _t('发件人名称'), _t('留空使用站点名称。')));
        $form->addInput(new TextInput('mailFrom', null, '', _t('发件地址'), _t('留空使用 SMTP 用户名。部分服务商要求与登录账号一致。')));
        $form->addInput(new TextInput('adminMail', null, '', _t('站长收件地址'), _t('留空使用文章作者的邮箱。')));
        $form->addInput(new Radio('notifyAdmin', ['1' => '是', '0' => '否'], '1', _t('新评论通知站长')));
        $form->addInput(new Radio('notifyReply', ['1' => '是', '0' => '否'], '1', _t('回复通知被回复者'), _t('只在回复已通过审核时发送。')));
        $form->addInput(new Radio('dropOnDeactivate', ['0' => '保留数据', '1' => '删除全部数据'], '0', _t('禁用插件时'), _t('选择「删除」后，禁用插件会清空说说、友链、相册、浏览量与点赞，无法恢复。')));
    }

    public static function personalConfig(Form $form)
    {
    }

    /**
     * Typecho 每次启用插件都会用默认值覆盖已有配置；这里在启用时保留用户之前的设置，
     * 只为新增的选项补上默认值。
     */
    public static function configHandle(array $settings, bool $isInit)
    {
        if ($isInit) {
            $old = self::restore();
            if ($old) $settings = array_merge($settings, array_intersect_key($old, $settings));
            self::backup(null);
        }
        Helper::configPlugin('NimbusKit', $settings);
        Config::reset();
    }
}
