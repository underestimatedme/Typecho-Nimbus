<?php
namespace TypechoPlugin\NimbusKit\Lib;

/**
 * ip2region xdb（IPv4）读取器，按文件格式逐次寻址，不整体载入内存。
 * 格式：256 字节头 + 256×256×8 字节向量索引（sPtr, ePtr，uint32 小端）
 *       + 区域数据 + 段索引（每条 14 字节：startIp u32、endIp u32、dataLen u16、dataPtr u32，均小端）。
 * 数据文件来自 https://github.com/lionsoul2014/ip2region（Apache-2.0），需站长自行下载。
 */
final class Ip2Region
{
    const HEADER = 256;
    const VECTOR_COLS = 256;
    const VECTOR_SIZE = 8;
    const SEGMENT_SIZE = 14;

    /** @var resource|null */
    private $fp = null;
    /** @var array<string,?string> */
    private $cache = [];

    public function __construct(string $file)
    {
        if (!is_file($file) || !is_readable($file) || filesize($file) < self::HEADER + self::VECTOR_COLS * self::VECTOR_COLS * self::VECTOR_SIZE) {
            throw new \RuntimeException('ip2region: 数据文件不存在或不完整');
        }
        $fp = fopen($file, 'rb');
        if ($fp === false) throw new \RuntimeException('ip2region: 无法打开数据文件');
        $h = fread($fp, 20);
        $ver = unpack('v', substr($h, 0, 2))[1];
        // xdb 3.x 头部带 IP 版本（偏移 16）与指针字节数（偏移 18）；只支持 IPv4、4 字节指针
        if ($ver >= 3 && strlen($h) >= 20) {
            $ipv = unpack('v', substr($h, 16, 2))[1];
            $ptrBytes = unpack('v', substr($h, 18, 2))[1];
            if (($ipv !== 0 && $ipv !== 4) || ($ptrBytes !== 0 && $ptrBytes !== 4)) {
                fclose($fp);
                throw new \RuntimeException('ip2region: 只支持 IPv4 数据文件');
            }
        }
        $this->fp = $fp;
    }

    public function __destruct()
    {
        if ($this->fp) fclose($this->fp);
    }

    private function read(int $offset, int $len): string
    {
        if (fseek($this->fp, $offset) !== 0) return '';
        $s = fread($this->fp, $len);
        return $s === false ? '' : $s;
    }

    /** 返回原始区域字符串（如「中国|0|广东省|深圳市|电信」），找不到返回 null。 */
    public function search(string $ip): ?string
    {
        if (array_key_exists($ip, $this->cache)) return $this->cache[$ip];
        $n = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? ip2long($ip) : false;
        if ($n === false) return $this->cache[$ip] = null;
        $n = $n & 0xFFFFFFFF;
        $il0 = ($n >> 24) & 0xFF;
        $il1 = ($n >> 16) & 0xFF;
        $v = $this->read(self::HEADER + ($il0 * self::VECTOR_COLS + $il1) * self::VECTOR_SIZE, 8);
        if (strlen($v) < 8) return $this->cache[$ip] = null;
        $sPtr = unpack('V', substr($v, 0, 4))[1];
        $ePtr = unpack('V', substr($v, 4, 4))[1];
        if ($sPtr === 0 || $ePtr < $sPtr) return $this->cache[$ip] = null;
        $l = 0;
        $h = intdiv($ePtr - $sPtr, self::SEGMENT_SIZE);
        while ($l <= $h) {
            $m = ($l + $h) >> 1;
            $seg = $this->read($sPtr + $m * self::SEGMENT_SIZE, self::SEGMENT_SIZE);
            if (strlen($seg) < self::SEGMENT_SIZE) break;
            $sip = unpack('V', substr($seg, 0, 4))[1];
            if ($n < $sip) { $h = $m - 1; continue; }
            $eip = unpack('V', substr($seg, 4, 4))[1];
            if ($n > $eip) { $l = $m + 1; continue; }
            $len = unpack('v', substr($seg, 8, 2))[1];
            $ptr = unpack('V', substr($seg, 10, 4))[1];
            if ($len <= 0 || $len > 1024) break;
            $r = $this->read($ptr, $len);
            return $this->cache[$ip] = (strlen($r) === $len ? $r : null);
        }
        return $this->cache[$ip] = null;
    }

    /**
     * 区域字符串转展示名：国内显示省份（去掉「省 / 市 / 自治区」等后缀），国外显示国家；
     * 兼容「国家|区域|省份|城市|ISP」与「国家|省份|城市|ISP」两种格式，0 视为空。
     */
    public static function label(?string $region): string
    {
        if ($region === null || $region === '') return '';
        $parts = array_values(array_filter(array_map('trim', explode('|', $region)), function ($p) { return $p !== '' && $p !== '0'; }));
        if (!$parts) return '';
        if (in_array('内网IP', $parts, true)) return '本地';
        $country = $parts[0];
        if ($country !== '中国') return $country;
        foreach (array_slice($parts, 1) as $p) {
            if (preg_match('/^(.+?)(省|市|壮族自治区|回族自治区|维吾尔自治区|自治区|特别行政区)$/u', $p, $m)) return $m[1];
            if (in_array($p, ['北京', '天津', '上海', '重庆', '香港', '澳门', '台湾', '内蒙古', '广西', '西藏', '宁夏', '新疆'], true)) return $p;
        }
        return isset($parts[1]) && !preg_match('/(电信|联通|移动|铁通|教育网|广电|鹏博士|阿里云|腾讯云)$/u', $parts[1]) ? $parts[1] : '中国';
    }

    /** 内网 / 保留地址。 */
    public static function isLocal(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) return false;
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
