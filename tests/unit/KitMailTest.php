<?php
use TypechoPlugin\NimbusKit\Lib\Smtp;
use TypechoPlugin\NimbusKit\Lib\Mailer;

test('message is multipart with CRLF and encoded subject', function () {
    $m = Smtp::buildMessage('a@b.com', '小站', 'c@d.com', '新评论', '<p>hi</p>', 'hi', 0, 'BOUND');
    assert_true(strpos($m, 'Content-Type: multipart/alternative; boundary="BOUND"') !== false);
    assert_true(strpos($m, 'Subject: =?UTF-8?B?' . base64_encode('新评论') . '?=') !== false);
    assert_true(strpos($m, 'From: =?UTF-8?B?' . base64_encode('小站') . '?= <a@b.com>') !== false);
    assert_true(strpos(str_replace("\r\n", '', $m), "\n") === false, 'only CRLF line endings');
    assert_true(substr($m, -9) === '--BOUND--');
});
test('ascii subject not encoded', fn() => assert_eq('Hello', Smtp::encodeHeader('Hello')));
test('dot stuffing', fn() => assert_eq("a\r\n..b\r\n..", Smtp::dotStuff("a\r\n.b\r\n.")));

$cfg = ['notifyAdmin' => '1', 'notifyReply' => '1', 'adminMail' => 'admin@site.com'];
test('new comment from visitor notifies admin', fn() => assert_eq([['kind' => 'admin', 'to' => 'admin@site.com']], Mailer::recipients(['mail' => 'v@x.com', 'status' => 'approved', 'authorId' => 0, 'ownerId' => 1], null, $cfg)));
test('owner comment does not notify admin', fn() => assert_eq([], Mailer::recipients(['mail' => 'admin@site.com', 'status' => 'approved', 'authorId' => 1, 'ownerId' => 1], null, $cfg)));
test('reply notifies parent and admin', fn() => assert_eq([['kind' => 'admin', 'to' => 'admin@site.com'], ['kind' => 'reply', 'to' => 'p@x.com']], Mailer::recipients(['mail' => 'v@x.com', 'status' => 'approved', 'authorId' => 0, 'ownerId' => 1], ['mail' => 'P@x.com'], $cfg)));
test('waiting reply does not notify parent', fn() => assert_eq([['kind' => 'admin', 'to' => 'admin@site.com']], Mailer::recipients(['mail' => 'v@x.com', 'status' => 'waiting', 'authorId' => 0, 'ownerId' => 1], ['mail' => 'p@x.com'], $cfg)));
test('replying to yourself does not notify', fn() => assert_eq([], Mailer::recipients(['mail' => 'v@x.com', 'status' => 'approved', 'authorId' => 0, 'ownerId' => 1], ['mail' => 'v@x.com'], ['notifyAdmin' => '0', 'notifyReply' => '1'])));
test('owner replying notifies parent only', fn() => assert_eq([['kind' => 'reply', 'to' => 'p@x.com']], Mailer::recipients(['mail' => 'admin@site.com', 'status' => 'approved', 'authorId' => 1, 'ownerId' => 1], ['mail' => 'p@x.com'], $cfg)));
test('render escapes content', function () {
    $r = Mailer::render('admin', ['site' => 'S', 'postTitle' => 'T', 'author' => '<b>x</b>', 'content' => '<script>', 'url' => 'http://a/']);
    assert_true(strpos($r['html'], '<script>') === false && strpos($r['html'], '&lt;script&gt;') !== false);
    assert_true(strpos($r['subject'], 'T') !== false && strpos($r['text'], 'http://a/') !== false);
});
