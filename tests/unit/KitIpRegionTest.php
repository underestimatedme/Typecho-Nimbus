<?php
use TypechoPlugin\NimbusKit\Lib\Ip2Region;
require_once __DIR__ . '/../xdb-builder.php';

$xdb = sys_get_temp_dir() . '/nimbus-test-' . getmypid() . '.xdb';
nimbus_build_xdb($xdb, [
    ['1.0.0.0', '1.0.0.255', '澳大利亚|0|0|0|0'],
    ['1.0.1.0', '1.0.3.255', '中国|0|福建省|福州市|电信'],
    ['10.0.0.0', '10.255.255.255', '0|0|0|内网IP|内网IP'],
    ['36.0.0.0', '36.1.255.255', '中国|0|广西壮族自治区|南宁市|移动'],
    ['58.30.0.0', '58.31.255.255', '中国|北京|北京市|联通'],
    ['223.255.255.0', '255.255.255.255', '美国|0|加利福尼亚|0|0'],
]);
test('xdb 查找：命中、边界、跨 /16 段', function () use ($xdb) {
    $r = new Ip2Region($xdb);
    assert_eq('中国|0|福建省|福州市|电信', $r->search('1.0.1.0'));
    assert_eq('中国|0|福建省|福州市|电信', $r->search('1.0.3.255'));
    assert_eq('澳大利亚|0|0|0|0', $r->search('1.0.0.200'));
    assert_eq(null, $r->search('1.0.4.1'));
    assert_eq('中国|0|广西壮族自治区|南宁市|移动', $r->search('36.1.200.9'));
    assert_eq('中国|北京|北京市|联通', $r->search('58.31.0.1'));
    assert_eq('美国|0|加利福尼亚|0|0', $r->search('255.255.255.255'));
    assert_eq(null, $r->search('2001:db8::1'));
    assert_eq(null, $r->search('not-an-ip'));
});
test('label：省份去后缀、国外显示国家、内网显示本地', function () {
    assert_eq('福建', Ip2Region::label('中国|0|福建省|福州市|电信'));
    assert_eq('广西', Ip2Region::label('中国|0|广西壮族自治区|南宁市|移动'));
    assert_eq('北京', Ip2Region::label('中国|北京|北京市|联通'));
    assert_eq('香港', Ip2Region::label('中国|0|香港特别行政区|0|0'));
    assert_eq('澳大利亚', Ip2Region::label('澳大利亚|0|0|0|0'));
    assert_eq('本地', Ip2Region::label('0|0|0|内网IP|内网IP'));
    assert_eq('中国', Ip2Region::label('中国|0|0|0|电信'));
    assert_eq('', Ip2Region::label(null));
    assert_eq('', Ip2Region::label('0|0|0|0|0'));
});
test('isLocal 识别内网与保留地址', function () {
    assert_true(Ip2Region::isLocal('127.0.0.1') && Ip2Region::isLocal('192.168.1.2') && Ip2Region::isLocal('::1'));
    assert_true(!Ip2Region::isLocal('8.8.8.8') && !Ip2Region::isLocal('garbage'));
});
test('xdb 文件缺失或损坏时报错', function () use ($xdb) {
    $ok = false;
    try { new Ip2Region($xdb . '.missing'); } catch (RuntimeException $e) { $ok = true; }
    assert_true($ok);
    $bad = $xdb . '.short'; file_put_contents($bad, str_repeat("\0", 1000));
    $ok = false;
    try { new Ip2Region($bad); } catch (RuntimeException $e) { $ok = true; }
    unlink($bad);
    assert_true($ok);
});
register_shutdown_function(function () use ($xdb) { @unlink($xdb); });
