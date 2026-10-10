<?php
namespace Nimbus;

/**
 * 评论相关：头像、UA 解析、表情替换、单条评论渲染。
 */
final class Comment
{
    public static function avatarUrl(string $mail, int $size, string $mirror): string
    {
        // GitHub 登录评论使用 {id}+{login}@users.noreply.github.com，直接用 GitHub 头像
        if (preg_match('/^(\d{1,12})\+[A-Za-z0-9-]{1,39}@users\.noreply\.github\.com$/i', trim($mail), $m)) {
            return 'https://avatars.githubusercontent.com/u/' . $m[1] . '?s=' . $size . '&v=4';
        }
        $hash = md5(strtolower(trim($mail)));
        return rtrim($mirror, '/') . '/' . $hash . '?s=' . $size . '&d=identicon';
    }

    /** @param array<string,string> $map name => url */
    public static function emojiReplace(string $text, array $map): string
    {
        return (string)preg_replace_callback('/:([a-z0-9_+-]+):/i', function ($m) use ($map) {
            $k = strtolower($m[1]);
            if (!isset($map[$k])) return $m[0];
            return '<img class="n-emoji" src="' . View::e($map[$k]) . '" alt="' . View::e($k) . '">';
        }, $text);
    }

    /** 粗粒度 UA 解析：浏览器与系统。 */
    public static function parseUa(string $ua): array
    {
        $browser = ''; $os = '';
        if ($ua !== '') {
            if (preg_match('/Edg(?:e|A|iOS)?\/([\d.]+)/', $ua)) $browser = 'Edge';
            elseif (preg_match('/OPR\/|Opera/', $ua)) $browser = 'Opera';
            elseif (preg_match('/MicroMessenger/', $ua)) $browser = '微信';
            elseif (preg_match('/QQBrowser/', $ua)) $browser = 'QQ 浏览器';
            elseif (preg_match('/UCBrowser/', $ua)) $browser = 'UC';
            elseif (preg_match('/Firefox\//', $ua)) $browser = 'Firefox';
            elseif (preg_match('/Chrome\//', $ua)) $browser = 'Chrome';
            elseif (preg_match('/Safari\//', $ua) && preg_match('/Version\//', $ua)) $browser = 'Safari';
            elseif (preg_match('/MSIE|Trident/', $ua)) $browser = 'IE';
            if (preg_match('/iPhone|iPad|iPod/', $ua)) $os = 'iOS';
            elseif (preg_match('/Android/', $ua)) $os = 'Android';
            elseif (preg_match('/HarmonyOS/', $ua)) $os = 'HarmonyOS';
            elseif (preg_match('/Windows/', $ua)) $os = 'Windows';
            elseif (preg_match('/Mac OS X|Macintosh/', $ua)) $os = 'macOS';
            elseif (preg_match('/Linux/', $ua)) $os = 'Linux';
        }
        return ['browser' => $browser, 'os' => $os];
    }

    /**
     * 单条评论渲染（threadedComments 回调）。
     * @param mixed $c Widget_Comments_Archive
     * @param mixed $options
     */
    public static function render($c, $options): void
    {
        $isAuthor = $c->authorId && $c->authorId == $c->ownerId;
        $mirror = (string)Options::get('GravatarMirror', 'https://cravatar.cn/avatar/');
        $avatar = self::avatarUrl((string)$c->mail, 96, $mirror);
        $ua = self::parseUa((string)$c->agent);
        $waiting = $c->status === 'waiting';
        $url = (string)$c->url;
        echo '<li id="' . View::e($c->theId) . '" class="n-comment' . ($isAuthor ? ' is-author' : '') . ($c->levels > 0 ? ' is-child' : '') . '" data-coid="' . (int)$c->coid . '">';
        echo '<div class="n-comment-main">';
        echo '<i class="n-comment-av" style="background-image:url(\'' . View::e($avatar) . '\')"></i>';
        echo '<div class="n-comment-body">';
        echo '<div class="n-comment-head">';
        if ($url !== '') echo '<a class="n-comment-name" href="' . View::e($url) . '" target="_blank" rel="noopener nofollow" data-no-router>' . View::e($c->author) . '</a>';
        else echo '<span class="n-comment-name">' . View::e($c->author) . '</span>';
        if ($isAuthor) echo '<span class="n-comment-badge">博主</span>';
        if ($ua['browser'] !== '' || $ua['os'] !== '') echo '<span class="n-comment-ua">' . View::e(trim($ua['os'] . ' · ' . $ua['browser'], ' ·')) . '</span>';
        $region = Kit::region((string)$c->ip);
        if ($region !== null && $region !== '') echo '<span class="n-comment-region" title="IP 属地">' . View::icon('pin') . View::e($region) . '</span>';
        echo '<time datetime="' . date('c', (int)$c->created) . '" title="' . date('Y-m-d H:i', (int)$c->created) . '">' . View::e(View::timeAgo((int)$c->created)) . '</time>';
        echo '</div>';
        if ($waiting) echo '<div class="n-comment-waiting">' . View::e((string)$options->commentStatus) . '</div>';
        echo '<div class="n-comment-content">' . $c->content . '</div>';
        echo '<div class="n-comment-foot">';
        $c->reply('<svg class="n-ico" viewBox="0 0 24 24"><path d="M9 17l-5-5 5-5M4 12h11a5 5 0 0 1 0 10h-1"/></svg>回复');
        echo '</div>';
        echo '</div></div>';
        if ($c->children) {
            echo '<ol class="n-comment-children">';
            $c->threadedComments($options);
            echo '</ol>';
        }
        echo '</li>';
    }
}
