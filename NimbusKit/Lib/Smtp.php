<?php
namespace TypechoPlugin\NimbusKit\Lib;

/**
 * 极简 SMTP 客户端：SSL / STARTTLS / 明文，AUTH LOGIN，multipart/alternative。
 * 无第三方依赖。失败抛 \RuntimeException。
 */
final class Smtp
{
    /** @var array */
    private $cfg;
    /** @var resource|null */
    private $sock = null;
    /** @var string[] 会话日志（不含密码） */
    public $log = [];

    public function __construct(array $cfg)
    {
        $this->cfg = array_merge(['host' => '', 'port' => 465, 'secure' => 'ssl', 'user' => '', 'pass' => '', 'timeout' => 10, 'helo' => 'localhost', 'verify' => true], $cfg);
    }

    public function send(string $from, string $fromName, string $to, string $subject, string $html, string $text): void
    {
        if ($this->cfg['host'] === '') throw new \RuntimeException('未配置 SMTP 主机');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) throw new \RuntimeException('收件地址无效：' . $to);
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) throw new \RuntimeException('发件地址无效：' . $from);
        try {
            $this->connect();
            $this->expect(220);
            $this->ehlo();
            if ($this->cfg['secure'] === 'tls') {
                $this->cmd('STARTTLS', 220);
                if (!stream_socket_enable_crypto($this->sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new \RuntimeException('STARTTLS 握手失败');
                $this->ehlo();
            }
            if ($this->cfg['user'] !== '') {
                $this->cmd('AUTH LOGIN', 334);
                $this->cmd(base64_encode($this->cfg['user']), 334, 'AUTH user');
                $this->cmd(base64_encode($this->cfg['pass']), 235, 'AUTH pass');
            }
            $this->cmd('MAIL FROM:<' . $from . '>', 250);
            $this->cmd('RCPT TO:<' . $to . '>', [250, 251]);
            $this->cmd('DATA', 354);
            $msg = self::buildMessage($from, $fromName, $to, $subject, $html, $text);
            $this->write(self::dotStuff($msg) . "\r\n.");
            $this->expect(250);
            $this->cmd('QUIT', 221);
        } finally {
            if ($this->sock) fclose($this->sock);
            $this->sock = null;
        }
    }

    private function connect(): void
    {
        $scheme = $this->cfg['secure'] === 'ssl' ? 'ssl://' : 'tcp://';
        $ctx = stream_context_create(['ssl' => ['verify_peer' => (bool)$this->cfg['verify'], 'verify_peer_name' => (bool)$this->cfg['verify'], 'SNI_enabled' => true, 'peer_name' => $this->cfg['host']]]);
        $errno = 0; $errstr = '';
        $sock = @stream_socket_client($scheme . $this->cfg['host'] . ':' . (int)$this->cfg['port'], $errno, $errstr, (int)$this->cfg['timeout'], STREAM_CLIENT_CONNECT, $ctx);
        if (!$sock) throw new \RuntimeException('无法连接 SMTP 服务器：' . ($errstr ?: 'error ' . $errno));
        stream_set_timeout($sock, (int)$this->cfg['timeout']);
        $this->sock = $sock;
    }

    private function ehlo(): void
    {
        $this->cmd('EHLO ' . $this->cfg['helo'], 250);
    }

    private function write(string $line): void
    {
        if (@fwrite($this->sock, $line . "\r\n") === false) throw new \RuntimeException('写入 SMTP 连接失败');
    }

    /** @param int|int[] $codes */
    private function cmd(string $line, $codes, ?string $logAs = null): string
    {
        $this->log[] = '> ' . ($logAs ?? $line);
        $this->write($line);
        return $this->expect($codes);
    }

    /** @param int|int[] $codes */
    private function expect($codes): string
    {
        $codes = (array)$codes;
        $resp = '';
        while (($line = fgets($this->sock, 1024)) !== false) {
            $resp .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }
        $this->log[] = '< ' . trim($resp);
        $code = (int)substr($resp, 0, 3);
        if (!in_array($code, $codes, true)) throw new \RuntimeException('SMTP 错误：' . trim($resp ?: '连接中断'));
        return $resp;
    }

    public static function encodeHeader(string $s): string
    {
        if (preg_match('/^[\x20-\x7e]*$/', $s)) return $s;
        return '=?UTF-8?B?' . base64_encode($s) . '?=';
    }

    public static function dotStuff(string $msg): string
    {
        return (string)preg_replace('/^\./m', '..', $msg);
    }

    public static function buildMessage(string $from, string $fromName, string $to, string $subject, string $html, string $text, ?int $date = null, ?string $boundary = null): string
    {
        $boundary = $boundary ?? ('=_nimbus_' . bin2hex(random_bytes(8)));
        $domain = substr(strrchr($from, '@') ?: '@localhost', 1);
        $h = [
            'Date: ' . date('r', $date ?? time()),
            'From: ' . ($fromName !== '' ? self::encodeHeader($fromName) . ' ' : '') . '<' . $from . '>',
            'To: <' . $to . '>',
            'Subject: ' . self::encodeHeader($subject),
            'Message-ID: <' . bin2hex(random_bytes(10)) . '@' . $domain . '>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'X-Mailer: NimbusKit',
        ];
        $body = '--' . $boundary . "\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . rtrim(chunk_split(base64_encode($text), 76, "\r\n")) . "\r\n"
            . '--' . $boundary . "\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . rtrim(chunk_split(base64_encode($html), 76, "\r\n")) . "\r\n"
            . '--' . $boundary . '--';
        return implode("\r\n", $h) . "\r\n\r\n" . $body;
    }
}
