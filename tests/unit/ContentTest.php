<?php
use Nimbus\Content;

$o = ['lightbox' => false, 'fold' => 30, 'host' => 'me.com'];
$lb = ['lightbox' => true, 'fold' => 30, 'host' => 'me.com'];
test('wraps table', fn() => assert_true(strpos(Content::renderFrom('<table><tr><td>1</td></tr></table>', $o)['html'], '<div class="n-table-wrap"><table') === 0));
test('img lazy + lightbox', function () use ($lb) { $r = Content::renderFrom('<p><img src="a.jpg"></p>', $lb); assert_true(strpos($r['html'], 'loading="lazy"') !== false, 'lazy'); assert_true(strpos($r['html'], 'data-lightbox') !== false && strpos($r['html'], 'href="a.jpg"') !== false, 'lightbox'); });
test('img inside link not wrapped', fn() => assert_true(strpos(Content::renderFrom('<a href="x"><img src="a.jpg"></a>', $lb)['html'], 'data-lightbox') === false));
test('code lang and fold', function () use ($o) { $code = implode("\n", array_fill(0, 40, 'x')); $r = Content::renderFrom('<pre><code class="language-php">' . $code . '</code></pre>', $o); assert_true(strpos($r['html'], 'data-lang="php"') !== false, 'lang'); assert_true(strpos($r['html'], 'data-fold') !== false, 'fold'); assert_eq(true, $r['hasCode']); });
test('short code not folded', function () use ($o) { $r = Content::renderFrom('<pre><code class="language-js">a</code></pre>', $o); assert_true(strpos($r['html'], 'data-fold') === false); });
test('mermaid converted', function () use ($o) { $r = Content::renderFrom('<pre><code class="language-mermaid">graph TD;A--&gt;B</code></pre>', $o); assert_true(strpos($r['html'], '<div class="mermaid">graph TD;A--&gt;B</div>') !== false, $r['html']); assert_eq(true, $r['hasMermaid']); });
test('detects math', fn() => assert_eq(true, Content::renderFrom('<p>$$x^2$$</p>', $o)['hasMath']));
test('no math by default', fn() => assert_eq(false, Content::renderFrom('<p>price $5 and $6</p>', $o)['hasMath']));
test('external link target', fn() => assert_true(strpos(Content::renderFrom('<a href="https://x.com/a">x</a>', $o)['html'], 'target="_blank"') !== false));
test('internal link untouched', fn() => assert_true(strpos(Content::renderFrom('<a href="https://me.com/a">x</a>', $o)['html'], 'target=') === false));
test('keeps utf8', fn() => assert_true(strpos(Content::renderFrom('<p>你好 😀</p>', $o)['html'], '你好 😀') !== false));
test('headings get ids', function () use ($o) { $r = Content::renderFrom('<h2>标题 一</h2><h3>Sub</h3>', $o); assert_true(strpos($r['html'], 'id="') !== false); assert_eq(2, count($r['toc'])); assert_eq('Sub', $r['toc'][1]['text']); assert_eq(3, $r['toc'][1]['level']); });
