<?php
namespace TypechoPlugin\NimbusKit;

use TypechoPlugin\NimbusKit\Lib\Mailer;
use TypechoPlugin\NimbusKit\Lib\Text;

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 后台写操作：/action/nimbus-kit?do=...
 */
class AdminAction extends \Widget\Base implements \Widget\ActionInterface
{
    public function action()
    {
        $this->user->pass('administrator');
        $this->security->protect();
        $do = (string)$this->request->get('do');
        $tab = 'moments';
        try {
            switch ($do) {
                case 'moment-save':
                    $id = (int)$this->request->get('id');
                    $content = trim((string)$this->request->get('content'));
                    $images = Text::urls((string)$this->request->get('images'));
                    if ($content === '' && empty($images)) throw new \InvalidArgumentException('说说内容和图片不能都为空');
                    Store::saveMoment($id ?: null, $content, $images, (string)$this->request->get('status'));
                    $msg = '说说已保存';
                    break;
                case 'moment-delete':
                    Store::deleteMoment((int)$this->request->get('id'));
                    $msg = '说说已删除';
                    break;
                case 'link-save':
                    $tab = 'links';
                    $url = Text::safeUrl((string)$this->request->get('url'));
                    $name = trim((string)$this->request->get('name'));
                    if ($name === '' || $url === '') throw new \InvalidArgumentException('名称和以 http(s):// 开头的网址必填');
                    Store::saveLink((int)$this->request->get('id') ?: null, [
                        'name' => mb_substr($name, 0, 100), 'url' => $url,
                        'avatar' => Text::safeUrl((string)$this->request->get('avatar')),
                        'desc' => mb_substr(trim((string)$this->request->get('desc')), 0, 500),
                        'sort' => (int)$this->request->get('sort'), 'status' => (string)$this->request->get('status'),
                    ]);
                    $msg = '友链已保存';
                    break;
                case 'link-delete':
                    $tab = 'links';
                    Store::deleteLink((int)$this->request->get('id'));
                    $msg = '友链已删除';
                    break;
                case 'album-save':
                    $tab = 'albums';
                    $title = trim((string)$this->request->get('title'));
                    $photos = Text::photos((string)$this->request->get('photos'));
                    if ($title === '') throw new \InvalidArgumentException('相册标题必填');
                    Store::saveAlbum((int)$this->request->get('id') ?: null, [
                        'title' => mb_substr($title, 0, 200), 'cover' => Text::safeUrl((string)$this->request->get('cover')),
                        'description' => trim((string)$this->request->get('description')), 'photos' => $photos,
                        'sort' => (int)$this->request->get('sort'),
                    ]);
                    $msg = '相册已保存（' . count($photos) . ' 张照片）';
                    break;
                case 'album-delete':
                    $tab = 'albums';
                    Store::deleteAlbum((int)$this->request->get('id'));
                    $msg = '相册已删除';
                    break;
                case 'test-mail':
                    $tab = 'mail';
                    $to = trim((string)$this->request->get('to'));
                    $o = \Helper::options();
                    Hooks::deliver($to, Mailer::render('test', ['site' => (string)$o->title, 'siteUrl' => (string)$o->siteUrl]));
                    $msg = '测试邮件已发送到 ' . $to;
                    break;
                default:
                    throw new \InvalidArgumentException('未知操作');
            }
            \Widget\Notice::alloc()->set($msg, 'success');
        } catch (\Throwable $e) {
            \Widget\Notice::alloc()->set($e->getMessage(), 'error');
        }
        $this->response->redirect(\Typecho\Common::url('extending.php?panel=' . urlencode(Plugin::PANEL) . '&tab=' . $tab, (string)$this->options->adminUrl));
    }
}
