<?php
use Nimbus\Bing;

$sample = '{"images":[{"url":"/th?id=OHR.BabcockSP_ZH-CN5266848536_1920x1080.jpg&rf=x&pid=hp","urlbase":"/th?id=OHR.BabcockSP_ZH-CN5266848536","copyright":"格莱德溪磨坊，巴布科克州立公园 (© dszc/Getty Images)","copyrightlink":"https://www.bing.com/search?q=x&form=hpcapt","title":"秋色中转动的水轮"}]}';
test('必应接口解析：图片地址、标题、版权、链接', function () use ($sample) {
    $b = Bing::parse($sample, 'cn.bing.com');
    assert_eq('https://cn.bing.com/th?id=OHR.BabcockSP_ZH-CN5266848536_1920x1080.jpg', $b['url']);
    assert_eq('秋色中转动的水轮', $b['title']);
    assert_true(strpos($b['copyright'], 'Getty') !== false);
    assert_eq('https://www.bing.com/search?q=x&form=hpcapt', $b['link']);
    assert_eq('https://cn.bing.com/th?id=OHR.BabcockSP_ZH-CN5266848536_UHD.jpg', Bing::parse($sample, 'cn.bing.com', 'UHD')['url']);
});
test('必应接口解析：异常数据被拒绝', function () use ($sample) {
    assert_eq(null, Bing::parse('not json', 'cn.bing.com'));
    assert_eq(null, Bing::parse('{"images":[]}', 'cn.bing.com'));
    assert_eq(null, Bing::parse(str_replace('/th?id=OHR.BabcockSP_ZH-CN5266848536"', '//evil.com/x.jpg"', $sample), 'cn.bing.com'));
    $b = Bing::parse(str_replace('https://www.bing.com/search', 'javascript:alert(1)//', $sample), 'cn.bing.com');
    assert_eq('', $b['link']);
});
