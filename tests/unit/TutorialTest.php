<?php
use Nimbus\Tutorial;

test('教程设置解析：缩略名、可选封面、过滤非法值', function () {
    $c = Tutorial::parseConfig(['typecho-theme', 'go-basics|https://img.example.com/go.png', ' | x', 'bad slug!', 'rust|javascript:alert(1)', '中文教程|/usr/uploads/a.jpg']);
    assert_eq(['typecho-theme' => '', 'go-basics' => 'https://img.example.com/go.png', 'rust' => '', '中文教程' => '/usr/uploads/a.jpg'], $c);
});
test('章节排序：chapter 优先，其余按发布时间', function () {
    $rows = [
        ['cid' => 1, 'created' => 300, 'chapter' => null],
        ['cid' => 2, 'created' => 100, 'chapter' => 2],
        ['cid' => 3, 'created' => 200, 'chapter' => 1],
        ['cid' => 4, 'created' => 50, 'chapter' => null],
        ['cid' => 5, 'created' => 400, 'chapter' => 10],
    ];
    assert_eq([3, 2, 5, 4, 1], array_column(Tutorial::sortChapters($rows), 'cid'));
});
