<?php
/**
 * 测试用 xdb 生成器：按 ip2region 格式写出小型 IPv4 数据文件（段在 /16 边界切分）。
 * @param array<int,array{0:string,1:string,2:string}> $segments [起始IP, 结束IP, 区域]（需有序、不重叠）
 */
function nimbus_build_xdb(string $file, array $segments): void
{
    $split = [];
    foreach ($segments as [$s, $e, $r]) {
        $a = ip2long($s) & 0xFFFFFFFF; $b = ip2long($e) & 0xFFFFFFFF;
        while ($a <= $b) {
            $blockEnd = ($a | 0xFFFF);
            $end = min($b, $blockEnd);
            $split[] = [$a, $end, $r];
            if ($end === 0xFFFFFFFF) break;
            $a = $end + 1;
        }
    }
    $header = str_pad(pack('vvVVV', 2, 1, time(), 0, 0), 256, "\0");
    $vectorLen = 256 * 256 * 8;
    $dataOff = 256 + $vectorLen;
    $data = ''; $ptrs = [];
    foreach ($split as $seg) {
        if (!isset($ptrs[$seg[2]])) { $ptrs[$seg[2]] = $dataOff + strlen($data); $data .= $seg[2]; }
    }
    $segOff = $dataOff + strlen($data);
    $index = ''; $vector = array_fill(0, 65536, [0, 0]);
    foreach ($split as $i => [$a, $b, $r]) {
        $p = $segOff + $i * 14;
        $index .= pack('VVvV', $a, $b, strlen($r), $ptrs[$r]);
        $k = ($a >> 16) & 0xFFFF;
        if ($vector[$k][0] === 0) $vector[$k][0] = $p;
        $vector[$k][1] = $p + 14; // 与官方生成器一致：ePtr 指向最后一条之后
    }
    $vbin = '';
    foreach ($vector as [$s, $e]) $vbin .= pack('VV', $s, $e);
    $header = substr_replace($header, pack('VV', $segOff, $segOff + strlen($index) - 14), 8, 8);
    file_put_contents($file, $header . $vbin . $data . $index);
}
