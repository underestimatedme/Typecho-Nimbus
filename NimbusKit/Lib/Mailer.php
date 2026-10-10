<?php
namespace TypechoPlugin\NimbusKit\Lib;

/**
 * 通知邮件：收件人决策与模板。纯函数。
 */
final class Mailer
{
    /**
     * @param array $comment  author, mail, status, authorId, ownerId
     * @param array|null $parent author, mail
     * @param array $cfg notifyAdmin, notifyReply, adminMail
     * @return array<int,array{kind:string,to:string}>
     */
    public static function recipients(array $comment, ?array $parent, array $cfg): array
    {
        $out = [];
        if (($comment['status'] ?? '') === 'spam') return $out;
        $mail = strtolower(trim((string)($comment['mail'] ?? '')));
        $admin = strtolower(trim((string)($cfg['adminMail'] ?? '')));
        $byOwner = !empty($comment['authorId']) && (int)$comment['authorId'] === (int)($comment['ownerId'] ?? -1);
        if (($cfg['notifyAdmin'] ?? '0') === '1' && filter_var($admin, FILTER_VALIDATE_EMAIL) && !$byOwner && $mail !== $admin) {
            $out[] = ['kind' => 'admin', 'to' => $admin];
        }
        if (($cfg['notifyReply'] ?? '0') === '1' && $parent && ($comment['status'] ?? '') === 'approved') {
            $pm = strtolower(trim((string)($parent['mail'] ?? '')));
            if (filter_var($pm, FILTER_VALIDATE_EMAIL) && $pm !== $mail && !in_array($pm, array_column($out, 'to'), true)) {
                $out[] = ['kind' => 'reply', 'to' => $pm];
            }
        }
        return $out;
    }

    /**
     * @param array $v site, siteUrl, postTitle, url, author, content, status, parentAuthor, parentContent
     * @return array{subject:string,html:string,text:string}
     */
    public static function render(string $kind, array $v): array
    {
        $e = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
        $site = (string)($v['site'] ?? '');
        $title = (string)($v['postTitle'] ?? '');
        if ($kind === 'reply') {
            $subject = '你在「' . $site . '」的评论有了新回复';
            $lead = $e($v['author'] ?? '') . ' 回复了你在《' . $e($title) . '》下的评论：';
            $quote = '<div style="margin:14px 0;padding:10px 14px;border-left:3px solid #cbd5e1;color:#64748b;font-size:13px">' . $e($v['parentAuthor'] ?? '') . '：' . nl2br($e($v['parentContent'] ?? '')) . '</div>';
            $textLead = ($v['author'] ?? '') . ' 回复了你在《' . $title . '》下的评论。' . "\n\n你的评论：" . ($v['parentContent'] ?? '');
        } elseif ($kind === 'test') {
            $subject = '「' . $site . '」NimbusKit 测试邮件';
            $lead = '这是一封测试邮件。能看到它，说明邮件通知配置正确。';
            $quote = '';
            $textLead = $lead;
        } else {
            $subject = '「' . $site . '」《' . $title . '》有新评论';
            $lead = $e($v['author'] ?? '') . ' 在《' . $e($title) . '》发表了评论' . (($v['status'] ?? '') === 'waiting' ? '（待审核）' : '') . '：';
            $quote = '';
            $textLead = ($v['author'] ?? '') . ' 在《' . $title . '》发表了评论' . (($v['status'] ?? '') === 'waiting' ? '（待审核）' : '') . '。';
        }
        $content = (string)($v['content'] ?? '');
        $url = (string)($v['url'] ?? ($v['siteUrl'] ?? ''));
        $body = $kind === 'test' ? '' : '<div style="margin:14px 0;padding:14px 16px;background:#f1f5f9;border-radius:10px;font-size:15px;line-height:1.7;color:#0f172a">' . nl2br($e($content)) . '</div>';
        $btn = $url !== '' ? '<p style="margin:22px 0 0"><a href="' . $e($url) . '" style="display:inline-block;padding:10px 20px;border-radius:999px;background:#3b82f6;color:#fff;text-decoration:none;font-size:14px">查看原文</a></p>' : '';
        $html = '<!doctype html><html><body style="margin:0;padding:24px;background:#f4f7fb;font-family:-apple-system,BlinkMacSystemFont,\'PingFang SC\',\'Microsoft YaHei\',sans-serif">'
            . '<div style="max-width:560px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px -12px rgba(15,23,42,.15)">'
            . '<div style="padding:18px 24px;background:linear-gradient(135deg,#60a5fa,#a78bfa);color:#fff;font-size:18px;font-weight:700">' . $e($site) . '</div>'
            . '<div style="padding:24px;color:#334155;font-size:14px;line-height:1.7"><p style="margin:0">' . $lead . '</p>' . $quote . $body . $btn . '</div>'
            . '<div style="padding:14px 24px;border-top:1px solid #e2e8f0;color:#94a3b8;font-size:12px">此邮件由 NimbusKit 自动发送，请勿直接回复。</div>'
            . '</div></body></html>';
        $text = $textLead . ($kind === 'test' ? '' : "\n\n" . $content) . ($url !== '' ? "\n\n" . $url : '') . "\n\n— " . $site;
        return ['subject' => $subject, 'html' => $html, 'text' => $text];
    }
}
