<?php
namespace TypechoPlugin\NimbusKit;

use TypechoPlugin\NimbusKit\Lib\Mailer;
use TypechoPlugin\NimbusKit\Lib\Smtp;
use TypechoPlugin\NimbusKit\Lib\Text;

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 评论钩子：
 * - finishComment（前台 Widget_Feedback 与后台回复 Widget_Comments_Edit）：排队发送通知邮件
 * - ajaxRespond（Widget_Feedback，权重最低、最后执行）：只对带 nimbus_ajax=1 的请求返回 JSON
 * - mark（Widget_Comments_Edit）：待审核的回复被通过时通知被回复者
 */
final class Hooks
{
    /** @param mixed $w Widget\Feedback 或 Widget\Comments\Edit（已压入新评论行） */
    public static function finishComment($w)
    {
        if (Config::get('mailEnable') !== '1') return;
        try {
            self::queueMail(self::row($w), (string)$w->permalink, null);
        } catch (\Throwable $e) {
            error_log('[NimbusKit] mail queue: ' . $e->getMessage());
        }
    }

    /** @param mixed $feedback Widget\Feedback */
    public static function ajaxRespond($feedback)
    {
        $req = \Typecho\Request::getInstance();
        if (!Config::feature('ajaxComment') || (string)$req->get('nimbus_ajax') !== '1') return;
        $status = (string)$feedback->status;
        $body = json_encode(['ok' => true, 'data' => [
            'coid' => (int)$feedback->coid,
            'status' => $status,
            'message' => $status === 'approved' ? Lang::t('评论成功') : Lang::t('评论已提交，正在等待审核'),
        ]], JSON_UNESCAPED_UNICODE);
        \Typecho\Response::getInstance()
            ->setContentType('application/json')
            ->addResponder(function () use ($body) { echo $body; })
            ->respond();
    }

    /**
     * 后台审核：待审核的回复被通过时通知被回复者（站长此前已收到"待审核"通知，不再重复）。
     * @param array $comment 修改前的评论行
     */
    public static function mark($comment, $edit, $status)
    {
        if (Config::get('mailEnable') !== '1') return;
        if ($status !== 'approved' || ($comment['status'] ?? '') === 'approved' || empty($comment['parent'])) return;
        try {
            $db = \Typecho\Db::get();
            $post = $db->fetchRow($db->select()->from('table.contents')->where('cid = ?', (int)$comment['cid'])->limit(1));
            $link = $post ? \Widget\Base\Contents::alloc()->filter($post)['permalink'] . '#comment-' . (int)$comment['coid'] : (string)\Helper::options()->siteUrl;
            $row = self::row((object)$comment);
            $row['status'] = 'approved';
            self::queueMail($row, $link, ['reply']);
        } catch (\Throwable $e) {
            error_log('[NimbusKit] mark mail: ' . $e->getMessage());
        }
    }

    /** @param mixed $w */
    private static function row($w): array
    {
        return [
            'coid' => (int)$w->coid, 'cid' => (int)$w->cid, 'author' => (string)$w->author,
            'mail' => (string)$w->mail, 'text' => (string)$w->text, 'status' => (string)$w->status,
            'parent' => (int)$w->parent, 'authorId' => (int)$w->authorId, 'ownerId' => (int)$w->ownerId,
        ];
    }

    /** 站长收件地址：配置优先，否则第一个管理员的邮箱。 */
    private static function adminMail(): string
    {
        $cfg = (string)Config::get('adminMail');
        if ($cfg !== '') return $cfg;
        $db = \Typecho\Db::get();
        $row = $db->fetchRow($db->select('mail')->from('table.users')->where('group = ?', 'administrator')->order('uid', \Typecho\Db::SORT_ASC)->limit(1));
        return (string)($row['mail'] ?? '');
    }

    private static function queueMail(array $comment, string $permalink, ?array $onlyKinds): void
    {
        $db = \Typecho\Db::get();
        $parent = null;
        if ($comment['parent'] > 0) {
            $parent = $db->fetchRow($db->select('author', 'mail', 'text')->from('table.comments')->where('coid = ?', $comment['parent'])->limit(1)) ?: null;
        }
        $cfg = Config::all();
        $cfg['adminMail'] = self::adminMail();
        $list = Mailer::recipients($comment, $parent, $cfg);
        if ($onlyKinds !== null) $list = array_values(array_filter($list, function ($r) use ($onlyKinds) { return in_array($r['kind'], $onlyKinds, true); }));
        if (empty($list)) return;
        $post = $db->fetchRow($db->select('title')->from('table.contents')->where('cid = ?', $comment['cid'])->limit(1));
        $o = \Helper::options();
        $vars = [
            'site' => (string)$o->title, 'siteUrl' => (string)$o->siteUrl, 'postTitle' => htmlspecialchars_decode((string)($post['title'] ?? ''), ENT_QUOTES),
            'url' => $permalink, 'author' => $comment['author'], 'content' => Text::plain($comment['text']), 'status' => $comment['status'],
            'parentAuthor' => (string)($parent['author'] ?? ''), 'parentContent' => Text::plain((string)($parent['text'] ?? '')),
        ];
        register_shutdown_function(function () use ($list, $vars, $cfg) {
            self::releaseClient();
            foreach ($list as $r) {
                try {
                    self::deliver($r['to'], Mailer::render($r['kind'], $vars), $cfg);
                } catch (\Throwable $e) {
                    error_log('[NimbusKit] mail to ' . $r['to'] . ' failed: ' . $e->getMessage());
                }
            }
        });
    }

    /** 先把响应完整发给浏览器并关闭连接，再慢慢发邮件（非 PHP-FPM 环境也不让浏览器干等）。 */
    private static function releaseClient(): void
    {
        ignore_user_abort(true);
        if (!headers_sent()) {
            header('Connection: close');
            $len = ob_get_level() === 1 ? ob_get_length() : false;
            if ($len !== false) header('Content-Length: ' . $len);
        }
        while (ob_get_level() > 0) @ob_end_flush();
        @flush();
        if (function_exists('fastcgi_finish_request')) @fastcgi_finish_request();
    }

    /** @param array{subject:string,html:string,text:string} $m */
    public static function deliver(string $to, array $m, ?array $cfg = null): array
    {
        $cfg = $cfg ?? Config::all();
        $smtp = new Smtp(['host' => (string)$cfg['smtpHost'], 'port' => (int)$cfg['smtpPort'], 'secure' => (string)$cfg['smtpSecure'], 'user' => (string)$cfg['smtpUser'], 'pass' => (string)$cfg['smtpPass'], 'helo' => (string)(parse_url((string)\Helper::options()->siteUrl, PHP_URL_HOST) ?: 'localhost')]);
        $from = (string)$cfg['mailFrom'] !== '' ? (string)$cfg['mailFrom'] : (string)$cfg['smtpUser'];
        $name = (string)$cfg['mailFromName'] !== '' ? (string)$cfg['mailFromName'] : (string)\Helper::options()->title;
        $smtp->send($from, $name, $to, $m['subject'], $m['html'], $m['text']);
        return $smtp->log;
    }
}
