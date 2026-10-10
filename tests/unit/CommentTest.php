<?php
use Nimbus\Comment;

test('avatar url', fn() => assert_eq('https://cravatar.cn/avatar/' . md5('a@b.com') . '?s=80&d=identicon', Comment::avatarUrl('A@B.com ', 80, 'https://cravatar.cn/avatar/')));
test('emoji replace', fn() => assert_eq('hi <img class="n-emoji" src="/e/smile.png" alt="smile">', Comment::emojiReplace('hi :smile:', ['smile' => '/e/smile.png'])));
test('emoji unknown kept', fn() => assert_eq('hi :nope:', Comment::emojiReplace('hi :nope:', ['smile' => '/e/smile.png'])));
test('ua parse', function () {
    $r = Comment::parseUa('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    assert_eq('Chrome', $r['browser']); assert_eq('macOS', $r['os']);
    $r = Comment::parseUa('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1');
    assert_eq('Safari', $r['browser']); assert_eq('iOS', $r['os']);
    $r = Comment::parseUa('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0');
    assert_eq('Edge', $r['browser']); assert_eq('Windows', $r['os']);
    assert_eq('', Comment::parseUa('')['browser']);
});
test('GitHub noreply 邮箱使用 GitHub 头像', function () {
    assert_eq('https://avatars.githubusercontent.com/u/583231?s=80&v=4', \Nimbus\Comment::avatarUrl('583231+octocat@users.noreply.github.com', 80, 'https://cravatar.cn/avatar'));
    assert_true(strpos(\Nimbus\Comment::avatarUrl('octocat@users.noreply.github.com', 80, 'https://cravatar.cn/avatar'), 'cravatar.cn') !== false);
});
