<?php
namespace Nimbus;

/**
 * 短代码：tip collapse tabs/tab button video bilibili github hide。
 * 在 Markdown 转换后的 HTML 上处理；代码块内不解析；未知短代码原样保留。
 */
final class Shortcode
{
    const PAIRED = ['tab', 'tip', 'button', 'collapse', 'hide', 'tabs'];
    const SINGLE = ['video', 'bilibili', 'github'];
    const TIP_TYPES = ['info', 'success', 'warning', 'danger'];

    /** @param array $ctx canSeeHidden(bool) host(string) */
    public static function render(string $html, array $ctx = []): string
    {
        if (strpos($html, '[') === false) return $html;
        [$html, $vault] = self::protect($html);
        $html = self::unwrap($html);
        $see = !empty($ctx['canSeeHidden']);
        $host = strtolower((string)($ctx['host'] ?? ''));
        for ($round = 0; $round < 6; $round++) {
            $before = $html;
            foreach (self::PAIRED as $tag) {
                $html = (string)preg_replace_callback('#\[' . $tag . '((?:\s+[a-z-]+(?:=(?:"[^"\]]*"|\'[^\'\]]*\'|[^\s\]]+))?)*)\s*\]((?:(?!\[' . $tag . '[\s\]]).)*?)\[/' . $tag . '\]#su', function ($m) use ($tag, $see, $host) {
                    return self::paired($tag, self::attrs($m[1]), self::trimBr($m[2]), $see, $host);
                }, $html);
            }
            if ($html === $before) break;
        }
        $html = (string)preg_replace_callback('#\[(' . implode('|', self::SINGLE) . ')((?:\s+[a-z-]+(?:=(?:"[^"\]]*"|\'[^\'\]]*\'|[^\s\]]+))?)*)\s*/?\]#u', function ($m) {
            return self::single($m[1], self::attrs($m[2]));
        }, $html);
        $html = (string)preg_replace('#<p>\s*</p>#', '', $html);
        return self::restore($html, $vault);
    }

    /** RSS / 纯文本用：去掉短代码标记，保留文字，隐藏内容替换为提示。 */
    public static function strip(string $html): string
    {
        [$html, $vault] = self::protect($html);
        $tip = I18n::t('（此处内容需要评论后可见，请访问原文）');
        $html = (string)preg_replace('#\[hide[^\]]*\].*?\[/hide\]#su', $tip, $html);
        // 未闭合的 [hide]（例如摘要在 <!--more--> 处截断）一直隐藏到末尾
        $html = (string)preg_replace('#\[hide[^\]]*\].*$#su', $tip, $html);
        $html = (string)preg_replace('#\[(?:' . implode('|', self::SINGLE) . ')[^\]]*\]#u', '', $html);
        $html = (string)preg_replace('#\[/?(?:' . implode('|', array_diff(self::PAIRED, ['hide'])) . ')(?:\s[^\]]*)?\]#u', '', $html);
        return self::restore($html, $vault);
    }

    /** 去掉文本中的隐藏内容（用于摘要、搜索）。 */
    public static function dropHidden(string $text): string
    {
        $text = (string)preg_replace('#\[hide[^\]]*\].*?\[/hide\]#su', '', $text);
        return (string)preg_replace('#\[hide[^\]]*\].*$#su', '', $text);
    }

    // ---------------- Typecho 钩子 ----------------

    /** @var array<int,bool> */
    private static $allowed = [];

    /** contentEx：($content, $widget, $上一个钩子的结果) */
    public static function hookContent($content, $widget = null, $last = null)
    {
        $html = is_string($last) ? $last : (string)$content;
        if (strpos($html, '[') === false) return $html;
        if (self::isFeed()) return self::strip($html);
        $host = strtolower((string)parse_url((string)\Helper::options()->siteUrl, PHP_URL_HOST));
        return self::render($html, ['canSeeHidden' => self::canSee($widget), 'host' => $host]);
    }

    /** RSS / Atom 请求（Router::$current 可能被其他内部路由匹配改写，按路径判断更可靠） */
    private static function isFeed(): bool
    {
        $path = (string)\Typecho\Request::getInstance()->getPathInfo();
        return strpos($path, '/feed') === 0;
    }

    /** excerptEx：摘要一律不含隐藏内容与短代码标记 */
    public static function hookExcerpt($excerpt, $widget = null, $last = null)
    {
        $html = is_string($last) ? $last : (string)$excerpt;
        return strpos($html, '[') === false ? $html : self::strip($html);
    }

    /**
     * 是否可以看到回复可见内容：
     * - 本文作者，或「贡献者」及以上的登录用户；
     * - 装了 NimbusKit：本浏览器提交过的评论（签名 cookie，无法伪造）中有一条在本文已通过审核；
     * - 未装插件：记住的邮箱在本文有已通过的评论，且评论 IP 与当前访客一致（邮箱 cookie 可被伪造，IP 作为第二道校验）。
     */
    public static function canSee($widget): bool
    {
        $cid = $widget ? (int)$widget->cid : 0;
        if (isset(self::$allowed[$cid])) return self::$allowed[$cid];
        $ok = false;
        try {
            $user = \Widget\User::alloc();
            if ($user->hasLogin() && ($user->pass('contributor', true) || ($widget && (int)$widget->authorId === (int)$user->uid))) {
                $ok = true;
            } elseif ($cid > 0) {
                $db = \Typecho\Db::get();
                $ids = Kit::commentedIds();
                if ($ids !== null) {
                    $ok = $ids && (bool)$db->fetchRow($db->select('coid')->from('table.comments')
                        ->where('cid = ? AND status = ?', $cid, 'approved')->where('coid IN ?', array_slice($ids, -60))->limit(1));
                } else {
                    $mail = (string)\Typecho\Cookie::get('__typecho_remember_mail', '');
                    $ip = (string)\Typecho\Request::getInstance()->getIp();
                    $ok = $mail !== '' && $ip !== '' && (bool)$db->fetchRow($db->select('coid')->from('table.comments')
                        ->where('cid = ? AND mail = ? AND ip = ? AND status = ?', $cid, $mail, $ip, 'approved')->limit(1));
                }
            }
        } catch (\Throwable $e) {
            $ok = false;
        }
        return self::$allowed[$cid] = $ok;
    }

    // ---------------------------------------------------------------

    private static function protect(string $html): array
    {
        $vault = [];
        $html = (string)preg_replace_callback('#<(pre|code)\b[^>]*>.*?</\1>#si', function ($m) use (&$vault) {
            $k = "\x01NSC" . count($vault) . "\x01";
            $vault[$k] = $m[0];
            return $k;
        }, $html);
        return [$html, $vault];
    }

    private static function restore(string $html, array $vault): string
    {
        return $vault ? strtr($html, $vault) : $html;
    }

    /** 把 Markdown 段落包裹拆开：<p>[tip]</p> → [tip]，<p>[tip]正文 → [tip]<p>正文 */
    private static function unwrap(string $html): string
    {
        // button 是行内元素，留在段落里；其余为块级，拆开 Markdown 包上的 <p>
        $names = implode('|', array_diff(array_merge(self::PAIRED, self::SINGLE), ['button']));
        $html = (string)preg_replace('#<p>\s*(\[(?:' . $names . ')(?:\s[^\]]*)?\s*/?\])\s*(?:<br\s*/?>)?\s*#u', '$1<p>', $html);
        $html = (string)preg_replace('#\s*(?:<br\s*/?>)?\s*(\[/(?:' . $names . ')\])\s*</p>#u', '</p>$1', $html);
        $html = (string)preg_replace('#(\[/(?:' . $names . ')\])\s*(?:<br\s*/?>)\s*#u', '$1', $html);
        return (string)preg_replace('#<p>\s*</p>#', '', $html);
    }

    private static function trimBr(string $s): string
    {
        $s = (string)preg_replace('#^\s*(?:<br\s*/?>\s*)+#', '', $s);
        return (string)preg_replace('#(?:\s*<br\s*/?>)+\s*$#', '', $s);
    }

    private static function attrs(string $raw): array
    {
        $out = [];
        preg_match_all('#([a-z-]+)(?:=(?:"([^"]*)"|\'([^\']*)\'|([^\s\]]+)))?#u', $raw, $m, PREG_SET_ORDER);
        foreach ($m as $a) {
            $v = '';
            foreach ([2, 3, 4] as $k) if (isset($a[$k]) && $a[$k] !== '') { $v = $a[$k]; break; }
            $out[strtolower($a[1])] = html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return $out;
    }

    private static function url(string $u): string
    {
        $u = trim($u);
        if (preg_match('#^https?://[^\s<>"\']+$#i', $u)) return $u;
        if (preg_match('#^/(?!/)[^\s<>"\']*$#', $u)) return $u;
        if (preg_match('#^\#[\w-]*$#', $u)) return $u;
        return '';
    }

    private static function paired(string $tag, array $a, string $inner, bool $see, string $host): string
    {
        $e = function ($s) { return View::e((string)$s); };
        switch ($tag) {
            case 'tip':
                $type = in_array($a['type'] ?? '', self::TIP_TYPES, true) ? $a['type'] : 'info';
                $icon = ['info' => 'info', 'success' => 'check', 'warning' => 'alert', 'danger' => 'alert'][$type];
                return '<div class="n-sc-tip is-' . $type . '" role="note">' . View::icon($icon, 'n-sc-tip-ico') . '<div class="n-sc-tip-body">' . self::para($inner) . '</div></div>';
            case 'collapse':
                $title = $a['title'] ?? I18n::t('展开查看');
                return '<details class="n-sc-collapse"' . (array_key_exists('open', $a) ? ' open' : '') . '><summary>' . $e($title) . View::icon('chevron-down', 'n-sc-caret') . '</summary><div class="n-sc-collapse-body">' . self::para($inner) . '</div></details>';
            case 'tab':
                return '<div class="n-sc-tab-panel" data-title="' . $e($a['title'] ?? I18n::t('标签')) . '">' . self::para($inner) . '</div>';
            case 'tabs':
                preg_match_all('#<div class="n-sc-tab-panel" data-title="([^"]*)">#', $inner, $m);
                if (empty($m[1])) return $inner;
                $nav = '';
                foreach ($m[1] as $i => $t) $nav .= '<button type="button" role="tab" aria-selected="' . ($i === 0 ? 'true' : 'false') . '"' . ($i === 0 ? ' class="is-on"' : '') . '>' . $t . '</button>';
                $inner = (string)preg_replace('#^\s*</p>|<p>\s*$#', '', trim($inner));
                return '<div class="n-sc-tabs" data-tabs><div class="n-sc-tabs-nav" role="tablist">' . $nav . '</div>' . $inner . '</div>';
            case 'button':
                $url = self::url($a['url'] ?? '');
                $type = ($a['type'] ?? 'primary') === 'ghost' ? 'n-btn-ghost' : 'n-btn-primary';
                $text = trim(strip_tags($inner)) ?: I18n::t('链接');
                if ($url === '') return '<span class="n-btn n-sc-btn ' . $type . '">' . $e($text) . '</span>';
                $ext = preg_match('#^https?://([^/:]+)#i', $url, $hm) && strtolower($hm[1]) !== $host;
                return '<a class="n-btn n-sc-btn ' . $type . '" href="' . $e($url) . '"' . ($ext ? ' target="_blank" rel="noopener"' : '') . '>' . $e($text) . View::icon('arrow-right') . '</a>';
            case 'hide':
                if ($see) return '<div class="n-sc-hide is-open"><div class="n-sc-hide-tip">' . View::icon('lock') . I18n::e('以下为评论后可见的内容') . '</div>' . self::para($inner) . '</div>';
                return '<div class="n-sc-hide">' . View::icon('lock', 'n-sc-hide-ico') . '<span>' . I18n::t('此处内容需要{link}后可见', ['link' => '<a href="#comments">' . I18n::e('评论') . '</a>']) . '</span></div>';
        }
        return $inner;
    }

    private static function single(string $tag, array $a): string
    {
        $e = function ($s) { return View::e((string)$s); };
        switch ($tag) {
            case 'video':
                $src = self::url($a['src'] ?? '');
                if ($src === '') return '';
                $poster = self::url($a['poster'] ?? '');
                return '<video class="n-sc-video" controls preload="metadata" playsinline src="' . $e($src) . '"' . ($poster !== '' ? ' poster="' . $e($poster) . '"' : '') . '></video>';
            case 'bilibili':
                $bv = (string)($a['bv'] ?? '');
                if (!preg_match('#^BV[0-9A-Za-z]{10}$#', $bv)) return '';
                $page = max(1, (int)($a['page'] ?? 1));
                return '<div class="n-sc-embed"><iframe src="https://player.bilibili.com/player.html?bvid=' . $bv . '&amp;page=' . $page . '&amp;high_quality=1&amp;autoplay=0" loading="lazy" allowfullscreen referrerpolicy="no-referrer" sandbox="allow-scripts allow-same-origin allow-popups allow-presentation" title="Bilibili"></iframe></div>';
            case 'github':
                $repo = (string)($a['repo'] ?? '');
                if (!preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $repo) || strpos($repo, '..') !== false) return '';
                return '<a class="n-sc-gh" href="https://github.com/' . $e($repo) . '" target="_blank" rel="noopener" data-gh-repo="' . $e($repo) . '" data-no-router>' . View::icon('github', 'n-sc-gh-ico') . '<span class="n-sc-gh-body"><b>' . $e($repo) . '</b><em data-gh-desc>' . I18n::e('GitHub 仓库') . '</em><span class="n-sc-gh-meta"><span data-gh-lang></span><span>' . View::icon('star') . '<i data-gh-stars>—</i></span><span>' . View::icon('share') . '<i data-gh-forks>—</i></span></span></span></a>';
        }
        return '';
    }

    /** 内容若是裸文本，包一层 <p>；已是块级 HTML 则保持。 */
    private static function para(string $inner): string
    {
        $t = trim($inner);
        if ($t === '') return '';
        if (preg_match('#^<(p|div|ul|ol|table|blockquote|details|pre|h[1-6]|figure|video)\b#i', $t)) {
            return (string)preg_replace('#^</p>|<p>$#', '', $t);
        }
        $t = (string)preg_replace('#^</p>\s*|\s*<p>$#', '', $t);
        return strpos($t, '<p>') !== false ? '<p>' . $t . '</p>' : '<p>' . $t . '</p>';
    }
}
