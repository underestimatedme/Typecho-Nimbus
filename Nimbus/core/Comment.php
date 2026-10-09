<?php
namespace Nimbus;

/**
 * 评论相关：头像、渲染回调（Task 9 扩展）。
 */
final class Comment
{
    public static function avatarUrl(string $mail, int $size, string $mirror): string
    {
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
}
