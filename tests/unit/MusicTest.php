<?php
use Nimbus\Music;

test('music list parses full and partial lines, drops invalid', function () {
    $l = Music::parse("夜曲|周杰伦|https://a.com/1.mp3|https://a.com/1.jpg|https://a.com/1.lrc\n# 注释\n只有名字\n本地|我|/usr/uploads/a.wav\n坏的|x|javascript:alert(1)");
    assert_eq(2, count($l));
    assert_eq('https://a.com/1.lrc', $l[0]['lrc']);
    assert_eq('/usr/uploads/a.wav', $l[1]['url']);
    assert_eq('', $l[1]['cover']);
});
test('music cover/lrc unsafe urls dropped', fn() => assert_eq('', Music::parse('a|b|https://x/1.mp3|javascript:x|data:text')[0]['cover']));
test('empty title gets placeholder', fn() => assert_eq('未命名', Music::parse('|b|https://x/1.mp3')[0]['title']));
test('live2d 配置：关闭、开启、危险地址', function () {
    $opts = ['Live2dEnable' => '0', 'Live2dScript' => 'https://cdn.example.com/autoload.js', 'Live2dMobile' => '0'];
    \Nimbus\Options::setSource(function ($k) use (&$opts) { return $opts[$k] ?? null; });
    assert_eq(null, Music::live2d());
    $opts['Live2dEnable'] = '1';
    assert_eq(['script' => 'https://cdn.example.com/autoload.js', 'mobile' => false], Music::live2d());
    $opts['Live2dScript'] = 'javascript:alert(1)';
    assert_eq(null, Music::live2d());
});
