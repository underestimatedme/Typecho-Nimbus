<?php
namespace TypechoPlugin\NimbusKit\Lib;

/**
 * 最小 HTTP 客户端：优先 curl，否则用 stream。只用于服务端调用 GitHub 接口。
 */
final class Http
{
    /** @return array{status:int,body:string} */
    public static function request(string $method, string $url, array $headers = [], ?string $body = null, int $timeout = 8): array
    {
        $headers[] = 'User-Agent: NimbusKit';
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => false,
            ]);
            if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $res = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            if (PHP_VERSION_ID < 80000) curl_close($ch);
            return ['status' => $res === false ? 0 : $status, 'body' => $res === false ? '' : (string)$res];
        }
        $ctx = stream_context_create(['http' => [
            'method' => $method, 'header' => implode("\r\n", $headers), 'content' => (string)$body,
            'timeout' => $timeout, 'ignore_errors' => true, 'follow_location' => 0,
        ]]);
        $fp = @fopen($url, 'r', false, $ctx);
        if ($fp === false) return ['status' => 0, 'body' => ''];
        $meta = stream_get_meta_data($fp);
        $res = (string)stream_get_contents($fp);
        fclose($fp);
        $status = 0;
        $first = isset($meta['wrapper_data'][0]) ? (string)$meta['wrapper_data'][0] : '';
        if (preg_match('#\s(\d{3})(\s|$)#', $first, $m)) $status = (int)$m[1];
        return ['status' => $status, 'body' => $res];
    }
}
