<?php
namespace Nimbus;

/**
 * 静态资源加载优化（仍然零构建）：
 * - CSS：把 nimbus.css 里的 @import 合并成一个 nimbus.bundle.css（@import 串行发现，40 个小文件会明显拖慢首屏）。
 *   仓库里附带已合并好的文件；源文件被修改后，主题会在下次请求时自动重新合并（目录不可写时回落到 nimbus.css）。
 * - JS：为入口与当前页面的静态 import 链输出 <link rel="modulepreload">，让浏览器并行下载而不是逐层发现。
 */
final class Assets
{
    const CSS_ENTRY = 'nimbus.css';
    const CSS_BUNDLE = 'nimbus.bundle.css';

    private static function cssDir(): string
    {
        return NIMBUS_DIR . '/assets/css';
    }

    /** 解析入口文件：返回 [片段列表, 依赖文件列表]。片段为 ['raw', 文本] 或 ['import', 相对路径, 层名]。 */
    public static function parseEntry(string $css): array
    {
        $parts = []; $files = [];
        foreach (preg_split('/\r\n|\n|\r/', $css) as $line) {
            if (preg_match('/^\s*@import\s+url\(\s*["\']?([^"\')]+)["\']?\s*\)\s*(?:layer\(\s*([\w-]+)\s*\))?\s*;\s*$/', $line, $m)) {
                $parts[] = ['import', $m[1], $m[2] ?? ''];
                $files[] = $m[1];
            } else {
                $parts[] = ['raw', $line];
            }
        }
        return [$parts, $files];
    }

    /** 合并：import 的文件内容按层包进 @layer 块，其余原样保留。 */
    public static function bundle(string $entry, callable $read): string
    {
        [$parts] = self::parseEntry($entry);
        $out = "/* 由 nimbus.css 自动合并生成，请修改源文件（修改后主题会自动重新合并） */\n";
        foreach ($parts as $p) {
            if ($p[0] === 'raw') { $out .= $p[1] . "\n"; continue; }
            $body = rtrim((string)$read($p[1]));
            $out .= "/* --- {$p[1]} --- */\n" . ($p[2] !== '' ? "@layer {$p[2]} {\n{$body}\n}\n" : $body . "\n");
        }
        return $out;
    }

    /** @var string|null */
    private static $cssHref = null;

    /** 样式表地址：优先合并文件，带内容版本号。 */
    public static function cssHref(): string
    {
        if (self::$cssHref !== null) return self::$cssHref;
        $dir = self::cssDir();
        $entryFile = $dir . '/' . self::CSS_ENTRY;
        $bundleFile = $dir . '/' . self::CSS_BUNDLE;
        $entry = (string)@file_get_contents($entryFile);
        [, $files] = self::parseEntry($entry);
        $mt = (int)@filemtime($entryFile);
        foreach ($files as $f) $mt = max($mt, (int)@filemtime($dir . '/' . $f));
        $exists = is_file($bundleFile);
        $ok = $exists && (int)@filemtime($bundleFile) >= $mt;
        $writable = is_writable($dir);
        // 目录不可写时（例如 FTP 上传后时间戳先后不一），继续使用随主题发布的合并文件
        if (!$ok && $exists && !$writable) $ok = true;
        if (!$ok && $entry !== '' && $writable) {
            $css = self::bundle($entry, function ($f) use ($dir) { return (string)@file_get_contents($dir . '/' . $f); });
            $tmp = $bundleFile . '.' . getmypid() . '.tmp';
            if (@file_put_contents($tmp, $css) !== false && @rename($tmp, $bundleFile)) $ok = true;
            else @unlink($tmp);
        }
        $url = View::asset('css/' . ($ok ? self::CSS_BUNDLE : self::CSS_ENTRY));
        return self::$cssHref = $url . ($ok ? '.' . substr(md5((string)@filemtime($bundleFile)), 0, 6) : '');
    }

    /** 从模块源码中提取静态 import 的相对路径。 */
    public static function imports(string $js): array
    {
        preg_match_all('/^\s*import\s+(?:[^\'";]+?\s+from\s+)?[\'"](\.{1,2}\/[^\'"]+\.js)[\'"]/m', $js, $m);
        return $m[1];
    }

    /** 入口 + 页面模块的完整静态依赖（相对 assets/js 的路径）。 */
    public static function moduleGraph(array $roots): array
    {
        $base = NIMBUS_DIR . '/assets/js/';
        $seen = []; $queue = $roots;
        while ($queue) {
            $rel = array_shift($queue);
            if (isset($seen[$rel]) || count($seen) > 80) continue;
            $file = $base . $rel;
            if (!is_file($file)) continue;
            $seen[$rel] = true;
            $dir = dirname($rel);
            foreach (self::imports((string)file_get_contents($file)) as $imp) {
                $path = ($dir === '.' ? '' : $dir . '/') . $imp;
                // 规范化 ./ 与 ../
                $segs = [];
                foreach (explode('/', $path) as $s) {
                    if ($s === '' || $s === '.') continue;
                    if ($s === '..') { array_pop($segs); continue; }
                    $segs[] = $s;
                }
                $queue[] = implode('/', $segs);
            }
        }
        return array_keys($seen);
    }

    /** @var array<string,string>|null 相对路径 => 版本号 */
    private static $jsVer = null;

    /** assets/js 下全部模块的版本号（文件修改时间 + 大小），改动任何一个文件只影响它自己的地址。 */
    public static function jsVersions(): array
    {
        if (self::$jsVer !== null) return self::$jsVer;
        $base = NIMBUS_DIR . '/assets/js/';
        $out = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if (substr($f->getFilename(), -3) !== '.js') continue;
            $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($base)));
            if ($rel === 'admin.js') continue;
            $out[$rel] = substr(md5($f->getMTime() . '|' . $f->getSize() . '|' . NIMBUS_VERSION), 0, 8);
        }
        ksort($out);
        return self::$jsVer = $out;
    }

    private static function jsBase(): string
    {
        return (string)preg_replace('/\?v=.*$/', '', View::asset('js'));
    }

    /** 带版本号的模块地址。 */
    public static function jsUrl(string $rel): string
    {
        $v = self::jsVersions()[$rel] ?? NIMBUS_VERSION;
        return self::jsBase() . '/' . $rel . '?v=' . $v;
    }

    /**
     * Import map：把每个模块的原始地址映射到带版本号的地址。
     * 模块之间用相对路径 import（没有版本号），浏览器会长期缓存旧文件；有了映射，主题升级后改过的文件会重新下载。
     */
    public static function importMapTag(): string
    {
        $map = [];
        foreach (array_keys(self::jsVersions()) as $rel) $map[self::jsBase() . '/' . $rel] = self::jsUrl($rel);
        return '<script type="importmap">' . json_encode(['imports' => $map], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . '</script>' . "\n";
    }

    /** <link rel="modulepreload"> 标签（app.js 本身由 <script> 加载，不重复）。 */
    public static function preloadTags(string $page, array $extra = []): string
    {
        $page = preg_replace('/[^a-z0-9-]/', '', $page) ?: 'index';
        $roots = array_merge(['app.js', 'pages/' . $page . '.js'], $extra);
        $out = '';
        foreach (self::moduleGraph($roots) as $rel) {
            if ($rel === 'app.js') continue;
            $out .= '<link rel="modulepreload" href="' . View::e(self::jsUrl($rel)) . '">' . "\n";
        }
        return $out;
    }
}
