<?php
namespace Nimbus;

/**
 * 正文后处理：表格包裹、图片懒加载 / 灯箱、代码块标记、Mermaid 转换、外链、标题 id。
 */
final class Content
{
    /**
     * @param array $opts lightbox(bool) fold(int) host(string)
     * @return array{html:string,hasMath:bool,hasMermaid:bool,hasCode:bool,toc:array}
     */
    public static function renderFrom(string $html, array $opts): array
    {
        $out = ['html' => $html, 'hasMath' => false, 'hasMermaid' => false, 'hasCode' => false, 'toc' => []];
        if (trim($html) === '') return $out;
        $out['hasMath'] = (bool)preg_match('/\$\$[\s\S]+?\$\$|\\\\\([\s\S]+?\\\\\)|\\\\\[[\s\S]+?\\\\\]/', $html);

        $lightbox = !empty($opts['lightbox']);
        $fold = isset($opts['fold']) ? (int)$opts['fold'] : 0;
        $host = isset($opts['host']) ? strtolower((string)$opts['host']) : '';

        $prev = libxml_use_internal_errors(true);
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML('<?xml encoding="utf-8" ?><html><body><div id="n-root">' . $html . '</div></body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $xp = new \DOMXPath($doc);
        $root = $doc->getElementById('n-root');
        if (!$root) return $out;

        // 表格
        foreach (iterator_to_array($xp->query('.//table', $root)) as $t) {
            if ($t->parentNode && $t->parentNode->nodeName === 'div' && $t->parentNode->getAttribute('class') === 'n-table-wrap') continue;
            $wrap = $doc->createElement('div');
            $wrap->setAttribute('class', 'n-table-wrap');
            $t->parentNode->replaceChild($wrap, $t);
            $wrap->appendChild($t);
        }
        // 图片
        foreach (iterator_to_array($xp->query('.//img', $root)) as $img) {
            if (!$img->hasAttribute('loading')) $img->setAttribute('loading', 'lazy');
            if (!$img->hasAttribute('decoding')) $img->setAttribute('decoding', 'async');
            if (!$img->hasAttribute('alt')) $img->setAttribute('alt', '');
            $inLink = false;
            for ($p = $img->parentNode; $p && $p !== $root; $p = $p->parentNode) { if ($p->nodeName === 'a') { $inLink = true; break; } }
            if ($lightbox && !$inLink && $img->getAttribute('src') !== '') {
                $a = $doc->createElement('a');
                $a->setAttribute('data-lightbox', '');
                $a->setAttribute('href', $img->getAttribute('src'));
                $a->setAttribute('class', 'n-lightbox-link');
                $img->parentNode->replaceChild($a, $img);
                $a->appendChild($img);
            }
        }
        // 代码块
        foreach (iterator_to_array($xp->query('.//pre', $root)) as $pre) {
            $code = null;
            foreach ($pre->childNodes as $c) { if ($c->nodeName === 'code') { $code = $c; break; } }
            $cls = $code ? $code->getAttribute('class') : $pre->getAttribute('class');
            $lang = '';
            if (preg_match('/(?:^|\s)(?:language|lang)-([a-z0-9_+-]+)/i', $cls, $m)) $lang = strtolower($m[1]);
            if ($lang === 'mermaid') {
                $div = $doc->createElement('div');
                $div->setAttribute('class', 'mermaid');
                $div->appendChild($doc->createTextNode($code ? $code->textContent : $pre->textContent));
                $pre->parentNode->replaceChild($div, $pre);
                $out['hasMermaid'] = true;
                continue;
            }
            $out['hasCode'] = true;
            if ($lang !== '') $pre->setAttribute('data-lang', $lang);
            if ($code && $lang !== '' && strpos($code->getAttribute('class'), 'language-') === false) $code->setAttribute('class', trim($code->getAttribute('class') . ' language-' . $lang));
            $lines = substr_count(($code ? $code->textContent : $pre->textContent), "\n") + 1;
            $pre->setAttribute('data-lines', (string)$lines);
            if ($fold > 0 && $lines > $fold) $pre->setAttribute('data-fold', '');
        }
        // 外链
        foreach (iterator_to_array($xp->query('.//a[@href]', $root)) as $a) {
            $href = $a->getAttribute('href');
            if (!preg_match('#^https?://([^/:]+)#i', $href, $m)) continue;
            $h = strtolower($m[1]);
            if ($host !== '' && ($h === $host || substr($h, -strlen('.' . $host)) === '.' . $host)) continue;
            if (!$a->hasAttribute('target')) $a->setAttribute('target', '_blank');
            $rel = trim($a->getAttribute('rel') . ' noopener');
            $a->setAttribute('rel', $rel);
        }
        // 标题 id 与目录
        $used = [];
        $i = 0;
        foreach (iterator_to_array($xp->query('.//h2|.//h3|.//h4', $root)) as $h) {
            $i++;
            $text = trim($h->textContent);
            $id = $h->getAttribute('id');
            if ($id === '') {
                $id = 'h-' . $i;
                $h->setAttribute('id', $id);
            }
            $used[$id] = true;
            $out['toc'][] = ['id' => $id, 'text' => $text, 'level' => (int)substr($h->nodeName, 1)];
        }

        $res = '';
        foreach ($root->childNodes as $c) $res .= $doc->saveHTML($c);
        $out['html'] = $res;
        return $out;
    }

    /** @param mixed $archive */
    public static function render($archive): array
    {
        $host = (string)parse_url((string)\Helper::options()->siteUrl, PHP_URL_HOST);
        return self::renderFrom((string)$archive->content, [
            'lightbox' => Options::bool('LightboxEnable'),
            'fold' => Options::int('CodeFoldLines'),
            'host' => $host,
        ]);
    }
}
