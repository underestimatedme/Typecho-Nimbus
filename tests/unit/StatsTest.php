<?php
use Nimbus\Stats;
use Nimbus\Chart;

test('monthly 按月计数并跨年', function () {
    $now = gmmktime(12, 0, 0, 2, 15, 2026);
    $ts = [gmmktime(0, 0, 0, 2, 1, 2026), gmmktime(0, 0, 0, 1, 31, 2026), gmmktime(0, 0, 0, 12, 5, 2025), gmmktime(0, 0, 0, 2, 1, 2025)];
    $r = Stats::monthly($ts, $now, 3);
    assert_eq(['2025-12', '2026-01', '2026-02'], $r['labels']);
    assert_eq([1, 1, 1], $r['values']);
});
test('monthly 使用站点时区', function () {
    $now = gmmktime(12, 0, 0, 3, 10, 2026);
    $ts = [gmmktime(20, 0, 0, 2, 28, 2026)]; // UTC 2 月 28 日 20 点 = 东八区 3 月 1 日
    assert_eq([0, 1], Stats::monthly($ts, $now, 2, 8 * 3600)['values']);
    assert_eq([1, 0], Stats::monthly($ts, $now, 2, 0)['values']);
});
test('heatmap 周一开始、今天之后标记 future', function () {
    $now = gmmktime(10, 0, 0, 10, 8, 2026); // 周四
    $h = Stats::heatmap([$now, $now - 3600, $now - 86400 * 2], $now, 2);
    assert_eq(2, count($h['weeks']));
    assert_eq('2026-10-05', $h['weeks'][1][0]['date']);
    assert_eq(2, $h['weeks'][1][3]['n']);
    assert_eq(1, $h['weeks'][1][1]['n']);
    assert_true($h['weeks'][1][4]['future']);
    assert_true(!($h['weeks'][1][3]['future']));
    assert_eq(2, $h['max']);
    assert_eq(3, $h['total']);
});
test('level 分 0–4 级', function () {
    assert_eq(0, Stats::level(0, 5));
    assert_eq(1, Stats::level(1, 8));
    assert_eq(4, Stats::level(8, 8));
    assert_eq(0, Stats::level(3, 0));
});
test('shares 合并其他并排序', function () {
    $items = [];
    foreach (range(1, 10) as $i) $items[] = ['name' => 'c' . $i, 'count' => $i];
    $items[] = ['name' => 'zero', 'count' => 0];
    $r = Stats::shares($items, 3);
    assert_eq(['c10', 'c9', 'c8', '其他'], array_column($r, 'name'));
    assert_eq(28, $r[3]['count']);
    assert_eq(2, count(Stats::shares([['name' => 'a', 'count' => 1], ['name' => 'b', 'count' => 2]], 1)));
});
test('words 中文逐字、英文按词', function () {
    assert_eq(4 + 3, Stats::words('<p>你好世界 hello <b>big</b> world&nbsp;</p>'));
    assert_eq(2, Stats::words("don't stop-me"));
});
test('niceMax 取整齐刻度', function () {
    assert_eq(4, Chart::niceMax(0));
    assert_eq(8, Chart::niceMax(7));
    assert_eq(40, Chart::niceMax(37));
    assert_eq(120, Chart::niceMax(103));
    assert_true(Chart::niceMax(98765) >= 98765);
});
test('分类圆环中心显示去重文章数而不是分类归属次数', function () {
    $html = Chart::donut([['name' => 'A', 'count' => 2], ['name' => 'B', 'count' => 2]], '篇文章', 3);
    $doc = new DOMDocument();
    @$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($doc);
    assert_eq('3', $xpath->query('//*[@class="n-ch-center"]')->item(0)->textContent);
    assert_eq(['50%', '50%'], array_map(function ($el) { return $el->textContent; }, iterator_to_array($xpath->query('//em'))));
});
test('图表转义标签、空数据不输出', function () {
    $svg = Chart::line('t', ['<b>', 'x'], [1, 2]);
    assert_true(strpos($svg, '&lt;b&gt;') !== false, '&lt;b&gt;');
    assert_true(strpos($svg, '<b>') === false, '<b>');
    assert_eq('', Chart::line('t', [], []));
    assert_eq('', Chart::donut([['name' => 'a', 'count' => 0]], '篇'));
    $d = Chart::donut([['name' => '"x"', 'count' => 3], ['name' => 'y', 'count' => 1]], '篇');
    assert_true(strpos($d, '75%') !== false, '75%');
    assert_true(strpos($d, '&quot;x&quot;') !== false, '&quot;x&quot;');
    assert_true(strpos(Chart::bars(['1月'], [['name' => '文章', 'values' => [3], 'cls' => 'a']]), 'n-ch-bar') !== false, 'n-ch-bar');
});
