<?php
use Nimbus\Home;
use Nimbus\Options;

$homeStore = [];
Options::setSource(function ($key) use (&$homeStore) { return $homeStore[$key] ?? null; });

test('home actions retain reading and random defaults', function () {
    $actions = Home::actions('/archives/example.html');
    assert_eq('#n-posts', $actions[0]['url']);
    assert_eq('/archives/example.html', $actions[1]['url']);
    assert_eq(1, count(Home::actions('')));
});
test('home actions accept configured labels and safe destinations', function () use (&$homeStore) {
    $homeStore = ['HeroPrimaryLabel' => '从这里开始', 'HeroPrimaryUrl' => '/start.html', 'HeroSecondaryLabel' => '听一篇', 'HeroSecondaryUrl' => 'https://example.org/post'];
    $actions = Home::actions('/random.html');
    assert_eq('从这里开始', $actions[0]['label']);
    assert_eq('/start.html', $actions[0]['url']);
    assert_eq('https://example.org/post', $actions[1]['url']);
});
test('home actions reject executable, protocol-relative and malformed URLs', function () use (&$homeStore) {
    foreach (['javascript:alert(1)', '//example.org', 'https://example.org/"x', '/path with space', '/\\example.org', "https://example.org/\nfoo"] as $url) {
        $homeStore = ['HeroPrimaryUrl' => $url, 'HeroSecondaryUrl' => $url];
        $actions = Home::actions('');
        assert_eq('#n-posts', $actions[0]['url']);
        assert_eq(1, count($actions));
    }
    $homeStore = [];
});
