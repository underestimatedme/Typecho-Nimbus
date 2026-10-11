<?php
namespace Nimbus;

/**
 * 手写 SVG 图表（服务端渲染，无脚本也可看）。颜色全部来自 CSS 变量，随皮肤切换。
 * 动画由 CSS 控制：pathLength="1" 的描边动画、柱子 scaleY 生长。
 */
final class Chart
{
    private static function n($v): string
    {
        return rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.');
    }

    /** 坐标轴刻度：返回约 4 档的「好看」最大值。 */
    public static function niceMax(int $max): int
    {
        if ($max <= 4) return 4;
        $raw = $max / 4;
        $base = 10 ** (int)floor(log10($raw));
        foreach ([1, 2, 2.5, 3, 4, 5, 6, 8, 10] as $m) {
            $step = $m * $base;
            if ($step >= $raw && abs($step - round($step)) < 1e-9) return (int)round($step) * 4;
        }
        return (int)ceil($raw) * 4;
    }

    /**
     * 折线面积图。
     * @param string[] $labels
     * @param int[] $values
     */
    public static function line(string $id, array $labels, array $values, string $unit = ''): string
    {
        $w = 640; $h = 200; $l = 40; $r = 12; $t = 14; $b = 28;
        $cnt = count($values);
        if ($cnt === 0) return '';
        $max = self::niceMax((int)max($values));
        $x = function ($i) use ($cnt, $w, $l, $r) { return $cnt > 1 ? $l + ($w - $l - $r) * $i / ($cnt - 1) : $l; };
        $y = function ($v) use ($max, $h, $t, $b) { return $t + ($h - $t - $b) * (1 - $v / $max); };
        $pts = [];
        foreach ($values as $i => $v) $pts[] = self::n($x($i)) . ',' . self::n($y($v));
        $out = '<svg class="n-ch n-ch-line" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="' . I18n::e('折线图') . '">';
        $out .= '<defs><linearGradient id="' . View::e($id) . '-g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" style="stop-color:var(--accent);stop-opacity:.35"/><stop offset="1" style="stop-color:var(--accent);stop-opacity:0"/></linearGradient></defs>';
        for ($g = 0; $g <= 4; $g++) {
            $gy = self::n($t + ($h - $t - $b) * $g / 4);
            $out .= '<line class="n-ch-grid" x1="' . $l . '" x2="' . ($w - $r) . '" y1="' . $gy . '" y2="' . $gy . '"/>';
            $out .= '<text class="n-ch-axis" x="' . ($l - 8) . '" y="' . $gy . '" text-anchor="end" dominant-baseline="middle">' . (int)round($max * (4 - $g) / 4) . '</text>';
        }
        $step = max(1, (int)ceil($cnt / 7));
        foreach ($labels as $i => $lab) {
            if ($i % $step !== 0 && $i !== $cnt - 1) continue;
            $out .= '<text class="n-ch-axis" x="' . self::n($x($i)) . '" y="' . ($h - 8) . '" text-anchor="middle">' . View::e($lab) . '</text>';
        }
        $base = self::n($y(0));
        $out .= '<path class="n-ch-area" d="M' . self::n($x(0)) . ',' . $base . ' L' . implode(' L', $pts) . ' L' . self::n($x($cnt - 1)) . ',' . $base . ' Z" fill="url(#' . View::e($id) . '-g)"/>';
        $out .= '<polyline class="n-ch-stroke" pathLength="1" points="' . implode(' ', $pts) . '"/>';
        foreach ($values as $i => $v) {
            $out .= '<circle class="n-ch-dot" style="--i:' . $i . '" cx="' . self::n($x($i)) . '" cy="' . self::n($y($v)) . '" r="3.5"><title>' . View::e($labels[$i] . I18n::t('：') . $v . $unit) . '</title></circle>';
        }
        return $out . '</svg>';
    }

    /**
     * 分组柱状图。
     * @param string[] $labels
     * @param array<int,array{name:string,values:int[],cls:string}> $series
     */
    public static function bars(array $labels, array $series): string
    {
        $w = 640; $h = 200; $l = 34; $r = 8; $t = 14; $b = 28;
        $cnt = count($labels);
        if ($cnt === 0 || empty($series)) return '';
        $all = [0];
        foreach ($series as $s) $all = array_merge($all, $s['values']);
        $max = self::niceMax((int)max($all));
        $slot = ($w - $l - $r) / $cnt;
        $bw = min(16, $slot * 0.7 / count($series));
        $out = '<svg class="n-ch n-ch-bars" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="' . I18n::e('柱状图') . '">';
        for ($g = 0; $g <= 4; $g++) {
            $gy = self::n($t + ($h - $t - $b) * $g / 4);
            $out .= '<line class="n-ch-grid" x1="' . $l . '" x2="' . ($w - $r) . '" y1="' . $gy . '" y2="' . $gy . '"/>';
            $out .= '<text class="n-ch-axis" x="' . ($l - 8) . '" y="' . $gy . '" text-anchor="end" dominant-baseline="middle">' . (int)round($max * (4 - $g) / 4) . '</text>';
        }
        foreach ($labels as $i => $lab) {
            $cx = $l + $slot * ($i + 0.5);
            $out .= '<text class="n-ch-axis" x="' . self::n($cx) . '" y="' . ($h - 8) . '" text-anchor="middle">' . View::e($lab) . '</text>';
            foreach ($series as $k => $s) {
                $v = (int)($s['values'][$i] ?? 0);
                $bh = ($h - $t - $b) * $v / $max;
                $bx = $cx - $bw * count($series) / 2 + $k * $bw + 1;
                $out .= '<rect class="n-ch-bar ' . View::e($s['cls']) . '" style="--i:' . $i . '" x="' . self::n($bx) . '" y="' . self::n($h - $b - $bh) . '" width="' . self::n(max(1, $bw - 2)) . '" height="' . self::n($bh) . '" rx="3"><title>' . View::e($lab . ' ' . $s['name'] . I18n::t('：') . $v) . '</title></rect>';
            }
        }
        return $out . '</svg>';
    }

    /**
     * 环形图（返回 SVG + 图例 HTML）。
     * @param array<int,array{name:string,count:int}> $items
     */
    public static function donut(array $items, string $centerLabel): string
    {
        $total = array_sum(array_map(function ($i) { return (int)$i['count']; }, $items));
        if ($total <= 0) return '';
        $r = 70; $c = 2 * M_PI * $r; $acc = 0;
        $svg = '<svg class="n-ch n-ch-donut" viewBox="0 0 200 200" role="img" aria-label="' . I18n::e('环形图') . '"><circle class="n-ch-ring" cx="100" cy="100" r="' . $r . '"/>';
        $legend = '<ul class="n-ch-legend">';
        foreach ($items as $k => $it) {
            $frac = (int)$it['count'] / $total;
            $len = $frac * $c;
            $gap = count($items) > 1 ? min(3, $len * 0.3) : 0;
            $url = isset($it['url']) && $it['url'] !== '' ? (string)$it['url'] : '';
            $seg = '<circle class="n-ch-seg n-ch-c' . ($k % 8) . '" style="--i:' . $k . '" cx="100" cy="100" r="' . $r . '" stroke-dasharray="' . self::n(max(0.1, $len - $gap)) . ' ' . self::n($c) . '" stroke-dashoffset="' . self::n(-$acc) . '" transform="rotate(-90 100 100)"><title>' . View::e($it['name'] . I18n::t('：') . $it['count']) . '</title></circle>';
            $svg .= $url !== '' ? '<a href="' . View::e($url) . '" aria-label="' . View::e($it['name']) . '">' . $seg . '</a>' : $seg;
            $acc += $len;
            $row = '<i class="n-ch-c' . ($k % 8) . '"></i><span>' . View::e($it['name']) . '</span><b>' . (int)$it['count'] . '</b><em>' . self::n(round($frac * 100, 1)) . '%</em>';
            $legend .= '<li>' . ($url !== '' ? '<a href="' . View::e($url) . '">' . $row . '</a>' : $row) . '</li>';
        }
        $svg .= '<text class="n-ch-center" x="100" y="96" text-anchor="middle">' . $total . '</text><text class="n-ch-center-sub" x="100" y="120" text-anchor="middle">' . View::e($centerLabel) . '</text></svg>';
        return '<div class="n-ch-donut-wrap">' . $svg . $legend . '</ul></div>';
    }

    /** 发文热力图。$data 为 Stats::heatmap() 的结果。 */
    public static function heatmap(array $data): string
    {
        $cell = 11; $gap = 3; $top = 18; $left = 26;
        $weeks = count($data['weeks']);
        $w = $left + $weeks * ($cell + $gap); $h = $top + 7 * ($cell + $gap);
        $out = '<svg class="n-ch n-ch-heat" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="' . I18n::e('发文热力图') . '">';
        foreach ($data['months'] as [$wk, $mon]) {
            if ($wk > $weeks - 3 && $wk !== 0) continue;
            $out .= '<text class="n-ch-axis" x="' . ($left + $wk * ($cell + $gap)) . '" y="11">' . I18n::e('{m}月', ['m' => $mon]) . '</text>';
        }
        foreach ([0 => I18n::t('一'), 2 => I18n::t('三'), 4 => I18n::t('五')] as $i => $lab) {
            $out .= '<text class="n-ch-axis" x="0" y="' . ($top + $i * ($cell + $gap) + 9) . '">' . $lab . '</text>';
        }
        foreach ($data['weeks'] as $wi => $col) {
            foreach ($col as $di => $d) {
                if ($d['future']) continue;
                $lv = Stats::level((int)$d['n'], (int)$data['max']);
                $act = (int)$d['n'] > 0 ? ' data-day="' . View::e($d['date']) . '" tabindex="0" role="button" aria-label="' . I18n::e('{date}：{n} 篇', ['date' => $d['date'], 'n' => $d['n']]) . '"' : '';
                $out .= '<rect class="n-ch-hc l' . $lv . ($act ? ' is-on' : '') . '"' . $act . ' style="--i:' . $wi . '" x="' . ($left + $wi * ($cell + $gap)) . '" y="' . ($top + $di * ($cell + $gap)) . '" width="' . $cell . '" height="' . $cell . '" rx="2.5"><title>' . I18n::e('{date}：{n} 篇', ['date' => $d['date'], 'n' => $d['n']]) . '</title></rect>';
            }
        }
        return $out . '</svg>';
    }
}
