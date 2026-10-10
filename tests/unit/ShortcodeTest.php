<?php
use Nimbus\Shortcode;

$sc = function (string $html, bool $see = false) { return Shortcode::render($html, ['canSeeHidden' => $see, 'host' => 'me.com']); };

test('tip renders with whitelisted type and unwraps paragraphs', function () use ($sc) {
    $h = $sc('<p>[tip type="warning"]</p><p>小心</p><p>[/tip]</p>');
    assert_true(strpos($h, '<div class="n-sc-tip is-warning"') === 0, $h);
    assert_true(strpos($h, '<p>小心</p>') !== false);
    assert_true(strpos($sc('[tip type="evil onload=x"]a[/tip]'), 'is-info') !== false, 'bad type falls back');
});
test('inline paragraph form [tip]text[/tip]', function () use ($sc) {
    $h = $sc('<p>[tip]一行提示[/tip]</p>');
    assert_true(strpos($h, 'n-sc-tip') !== false && strpos($h, '一行提示') !== false && strpos($h, '[tip]') === false, $h);
});
test('collapse with title escaped and open flag', function () use ($sc) {
    $h = $sc('[collapse title="<b>x</b>" open]内容[/collapse]');
    assert_true(strpos($h, '<details class="n-sc-collapse" open>') !== false, $h);
    assert_true(strpos($h, '&lt;b&gt;x&lt;/b&gt;') !== false, 'title escaped');
});
test('tabs build nav and panels', function () use ($sc) {
    $h = $sc('[tabs][tab title="A"]一[/tab][tab title="B"]二[/tab][/tabs]');
    assert_eq(2, substr_count($h, 'role="tab"'));
    assert_eq(2, substr_count($h, 'class="n-sc-tab-panel"'));
    assert_true(strpos($h, '>A</button>') !== false && strpos($h, '>B</button>') !== false, $h);
});
test('button filters javascript url', function () use ($sc) {
    assert_true(strpos($sc('[button url="javascript:alert(1)"]点我[/button]'), 'javascript') === false);
    $h = $sc('[button url="https://x.com/a" type="ghost"]去看看[/button]');
    assert_true(strpos($h, 'href="https://x.com/a"') !== false && strpos($h, 'n-btn-ghost') !== false && strpos($h, 'target="_blank"') !== false, $h);
});
test('attribute quote breakout is escaped', function () use ($sc) {
    $h = $sc('[button url="https://x.com/&quot; onmouseover=&quot;alert(1)"]x[/button]');
    assert_true(strpos($h, 'onmouseover="') === false, $h);
});
test('bilibili requires valid BV', function () use ($sc) {
    assert_true(strpos($sc('[bilibili bv="BV1xx411c7mD"]'), 'player.bilibili.com/player.html?bvid=BV1xx411c7mD') !== false);
    assert_true(strpos($sc('[bilibili bv="javascript:1"]'), 'iframe') === false);
});
test('video and github', function () use ($sc) {
    assert_true(strpos($sc('[video src="https://v.com/a.mp4" poster="https://v.com/p.jpg"]'), '<video class="n-sc-video" controls preload="metadata" playsinline src="https://v.com/a.mp4" poster="https://v.com/p.jpg"></video>') !== false);
    assert_true(strpos($sc('[github repo="typecho/typecho"]'), 'data-gh-repo="typecho/typecho"') !== false);
    assert_true(strpos($sc('[github repo="../../x"]'), 'data-gh-repo') === false);
});
test('hide removes content unless allowed', function () use ($sc) {
    $h = $sc('<p>公开</p>[hide]<p>秘密 ZXQ</p>[/hide]');
    assert_true(strpos($h, 'ZXQ') === false, 'secret leaked');
    assert_true(strpos($h, 'n-sc-hide') !== false && strpos($h, '公开') !== false);
    assert_true(strpos($sc('[hide]秘密 ZXQ[/hide]', true), 'ZXQ') !== false, 'shown when allowed');
});
test('shortcodes inside code blocks are left alone', function () use ($sc) {
    $h = $sc('<pre><code>[tip]x[/tip] [hide]y[/hide]</code></pre><p><code>[button url="/"]b[/button]</code></p>');
    assert_true(strpos($h, '<pre><code>[tip]x[/tip] [hide]y[/hide]</code></pre>') !== false, $h);
    assert_true(strpos($h, '<code>[button url="/"]b[/button]</code>') !== false);
});
test('unknown shortcodes untouched', fn() => assert_eq('<p>[foo]bar[/foo] [1]</p>', $sc('<p>[foo]bar[/foo] [1]</p>')));
test('strip mode for feeds keeps text and drops hidden', function () {
    $h = Shortcode::strip('[tip]提示[/tip][hide]秘密ZXQ[/hide][bilibili bv="BV1xx411c7mD"]');
    assert_true(strpos($h, '提示') !== false && strpos($h, 'ZXQ') === false && strpos($h, '[') === false, $h);
});
