<?php
namespace Nimbus;

/**
 * SEO 头部：description、OpenGraph、Twitter Card、canonical、JSON-LD。
 */
final class Seo
{
    public static function head($archive): string
    {
        $o = \Helper::options();
        $isSingle = $archive->is('single');
        $title = $isSingle ? (string)$archive->title : (string)$o->title;
        $desc = $isSingle ? Post::excerpt($archive, 160) : (string)$o->description;
        $url = $isSingle ? (string)$archive->permalink : (string)$o->siteUrl;
        $image = $isSingle ? Post::thumb($archive) : (string)Options::get('LogoImage', '');
        $out = [];
        $out[] = '<meta name="description" content="' . View::e($desc) . '">';
        $out[] = '<link rel="canonical" href="' . View::e($url) . '">';
        $out[] = '<meta property="og:site_name" content="' . View::e((string)$o->title) . '">';
        $out[] = '<meta property="og:type" content="' . ($isSingle ? 'article' : 'website') . '">';
        $out[] = '<meta property="og:title" content="' . View::e($title) . '">';
        $out[] = '<meta property="og:description" content="' . View::e($desc) . '">';
        $out[] = '<meta property="og:url" content="' . View::e($url) . '">';
        if ($image !== '') $out[] = '<meta property="og:image" content="' . View::e($image) . '">';
        $out[] = '<meta name="twitter:card" content="' . ($image !== '' ? 'summary_large_image' : 'summary') . '">';
        $out[] = '<meta name="twitter:title" content="' . View::e($title) . '">';
        $out[] = '<meta name="twitter:description" content="' . View::e($desc) . '">';
        if ($isSingle) {
            $ld = [
                '@context' => 'https://schema.org',
                '@type' => $archive->is('post') ? 'BlogPosting' : 'WebPage',
                'headline' => $title,
                'description' => $desc,
                'url' => $url,
                'datePublished' => date('c', (int)$archive->created),
                'dateModified' => date('c', (int)$archive->modified),
                'author' => ['@type' => 'Person', 'name' => (string)$archive->author->screenName],
                'publisher' => ['@type' => 'Organization', 'name' => (string)$o->title],
            ];
            if ($image !== '') $ld['image'] = $image;
        } else {
            $ld = [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => (string)$o->title,
                'url' => (string)$o->siteUrl,
                'potentialAction' => ['@type' => 'SearchAction', 'target' => rtrim((string)$o->index, '/') . '/search/{search_term_string}/', 'query-input' => 'required name=search_term_string'],
            ];
        }
        $out[] = '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
        return implode("\n", $out) . "\n";
    }
}
