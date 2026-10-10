<?php
namespace TypechoPlugin\NimbusKit\Lib;

final class Sitemap
{
    /** @param array<int,array{loc:string,lastmod:?int,priority:string}> $urls */
    public static function xml(array $urls): string
    {
        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            if (empty($u['loc'])) continue;
            $out .= '  <url><loc>' . htmlspecialchars((string)$u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>';
            if (!empty($u['lastmod'])) $out .= '<lastmod>' . gmdate('Y-m-d\TH:i:s\Z', (int)$u['lastmod']) . '</lastmod>';
            if (!empty($u['priority'])) $out .= '<priority>' . htmlspecialchars((string)$u['priority'], ENT_XML1) . '</priority>';
            $out .= "</url>\n";
        }
        return $out . '</urlset>' . "\n";
    }
}
