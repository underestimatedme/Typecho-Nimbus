<?php
namespace TypechoPlugin\NimbusKit;

use TypechoPlugin\NimbusKit\Lib\Mailer;
use TypechoPlugin\NimbusKit\Lib\Smtp;
use TypechoPlugin\NimbusKit\Lib\Text;

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 评论完成钩子：邮件通知（响应结束后发送）与 AJAX 响应。
 */
final class Hooks
{
    /** @param mixed $feedback Widget\Feedback */
    public static function finishComment($feedback)
    {
        $comment = [
            'coid' => (int)$feedback->coid, 'cid' => (int)$feedback->cid, 'author' => (string)$feedback->author,
            'mail' => (string)$feedback->mail, 'text' => (string)$feedback->text, 'status' => (string)$feedback->status,
            'parent' => (int)$feedback->parent, 'authorId' => (int)$feedback->authorId, 'ownerId' => (int)$feedback->ownerId,
        ];
        if (Config::get('mailEnable') === '1') {
            try {
                self::queueMail($comment, (string)$feedback->permalink);
            } catch (\Throwable $e) {
                error_log('[NimbusKit] mail queue: ' . $e->getMessage());
            }
        }
        if (Config::feature('ajaxComment') && \Typecho\Request::getInstance()->isAjax()) {
            $waiting = $comment['status'] !== 'approved';
            $body = json_encode(['ok' => true, 'data' => [
                'coid' => $comment['coid'],
                'status' => $comment['status'],
                'message' => $waiting ? '评论已提交，正在等待审核' : '评论成功',
            ]], JSON_UNESCAPED_UNICODE);
            \Typecho\Response::getInstance()
                ->setContentType('application/json')
                ->addResponder(function () use ($body) { echo $body; })
                ->respond();
        }
    }

    private static function queueMail(array $comment, string $permalink): void
    {
        $db = \Typecho\Db::get();
        $parent = null;
        if ($comment['parent'] > 0) {
            $parent = $db->fetchRow($db->select('author', 'mail', 'text')->from('table.comments')->where('coid = ?', $comment['parent'])->limit(1)) ?: null;
        }
        $cfg = Config::all();
        if (empty($cfg['adminMail'])) {
            $owner = $db->fetchRow($db->select('mail')->from('table.users')->where('uid = ?', $comment['ownerId'])->limit(1));
            $cfg['adminMail'] = (string)($owner['mail'] ?? '');
        }
        $list = Mailer::recipients($comment, $parent, $cfg);
        if (empty($list)) return;
        $post = $db->fetchRow($db->select('title')->from('table.contents')->where('cid = ?', $comment['cid'])->limit(1));
        $o = \Helper::options();
        $vars = [
            'site' => (string)$o->title, 'siteUrl' => (string)$o->siteUrl, 'postTitle' => htmlspecialchars_decode((string)($post['title'] ?? ''), ENT_QUOTES),
            'url' => $permalink, 'author' => $comment['author'], 'content' => Text::plain($comment['text']), 'status' => $comment['status'],
            'parentAuthor' => (string)($parent['author'] ?? ''), 'parentContent' => Text::plain((string)($parent['text'] ?? '')),
        ];
        register_shutdown_function(function () use ($list, $vars, $cfg) {
            // 先把响应发给浏览器，再慢慢发邮件
            while (ob_get_level() > 0) @ob_end_flush();
            @flush();
            if (function_exists('fastcgi_finish_request')) @fastcgi_finish_request();
            foreach ($list as $r) {
                try {
                    self::deliver($r['to'], Mailer::render($r['kind'], $vars), $cfg);
                } catch (\Throwable $e) {
                    error_log('[NimbusKit] mail to ' . $r['to'] . ' failed: ' . $e->getMessage());
                }
            }
        });
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
