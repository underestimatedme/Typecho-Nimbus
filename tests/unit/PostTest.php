<?php
use Nimbus\Post;

test('Cuteen imgst field provides widget thumbnail and article cover', function () {
    $p = (object)['cid' => 901, 'fields' => (object)['thumb' => '', 'imgst' => ' https://img.example/legacy.jpg '], 'text' => '<img src="body.jpg">'];
    assert_eq('https://img.example/legacy.jpg', Post::thumb($p));
    assert_eq(true, Post::hasOwnImage($p));
});
test('Nimbus thumb field overrides legacy imgst', function () {
    $p = (object)['cid' => 902, 'fields' => (object)['thumb' => '/new.jpg', 'imgst' => '/old.jpg'], 'text' => ''];
    assert_eq('/new.jpg', Post::thumb($p));
});

test('thumbFrom prefers field', fn() => assert_eq('https://a/b.jpg', Post::thumbFrom('https://a/b.jpg', '<img src="x.jpg">', [], 1, '/t')));
test('thumbFrom extracts first img', fn() => assert_eq('x.jpg', Post::thumbFrom('', '<p>hi</p><img alt="" src="x.jpg"><img src="y.jpg">', [], 1, '/t')));
test('thumbFrom extracts markdown img', fn() => assert_eq('https://p/1.jpg', Post::thumbFrom('', "<!--markdown-->text\n![a](https://p/1.jpg)\n", [], 1, '/t')));
test('thumbFrom uses pool by cid mod', fn() => assert_eq('b', Post::thumbFrom(null, '', ['a', 'b', 'c'], 4, '/t')));
test('thumbFrom falls back to builtin', fn() => assert_eq('/t/thumb-03.svg', Post::thumbFrom(null, '', [], 8, '/t')));
test('excerptFrom strips and truncates', fn() => assert_eq('你好世界这是…', Post::excerptFrom(null, "<p>你好  世界</p>\n<p>这是一段很长的正文</p>", 6)));
test('excerptFrom prefers field', fn() => assert_eq('自定义', Post::excerptFrom('自定义', '<p>abc</p>', 100)));
test('excerptFrom keeps latin spaces', fn() => assert_eq('hello world', Post::excerptFrom(null, '<p>hello   world</p>', 100)));
test('wordCount counts cjk chars and latin words', fn() => assert_eq(6, Post::wordCount('<p>你好，世界 hello world</p>')));
test('readingTime min 1', fn() => assert_eq(1, Post::readingTime(10)));
test('readingTime ceil', fn() => assert_eq(3, Post::readingTime(900)));
test('filterCids keeps order and drops missing', fn() => assert_eq([['cid' => 3], ['cid' => 1]], Post::filterCids([['cid' => 1], ['cid' => 3], ['cid' => 9]], ['3', '1', '7'])));
test('excerptFrom drops code blocks', fn() => assert_eq('正文', Post::excerptFrom(null, '<p>正文</p><pre><code>$x = 1;</code></pre>', 100)));
test('seo json-ld cannot break out of script', function () {
    $json = json_encode(['d' => '</script><script>alert(1)</script>'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS);
    assert_true(strpos($json, '</script') === false);
});
test('excerpt never contains hidden content', fn() => assert_true(strpos(Post::excerptFrom(null, '<p>公开</p>[hide]<p>秘密ZXQ</p>[/hide]', 100), 'ZXQ') === false));
