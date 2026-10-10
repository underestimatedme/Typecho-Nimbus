<?php
namespace Nimbus;

/**
 * 音乐播放器配置。
 */
final class Music
{
    public static function safeUrl(string $u): string
    {
        $u = trim($u);
        if (preg_match('#^https?://[^\s<>"\']+$#i', $u)) return $u;
        if (preg_match('#^/(?!/)[^\s<>"\']*$#', $u)) return $u;
        return '';
    }

    /**
     * 解析「歌名|歌手|音频URL|封面URL|歌词URL」，无效行（无音频地址）丢弃。
     * @return array<int,array{title:string,artist:string,url:string,cover:string,lrc:string}>
     */
    public static function parse(string $raw): array
    {
        $out = [];
        foreach (preg_split('/\R/u', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            $p = array_map('trim', explode('|', $line));
            $url = self::safeUrl($p[2] ?? '');
            if ($url === '') continue;
            $out[] = [
                'title' => mb_substr($p[0] ?? '', 0, 100) ?: '未命名',
                'artist' => mb_substr($p[1] ?? '', 0, 100),
                'url' => $url,
                'cover' => self::safeUrl($p[3] ?? ''),
                'lrc' => self::safeUrl($p[4] ?? ''),
            ];
        }
        return $out;
    }

    /** 前端配置；未开启返回 null。 */
    public static function config(): ?array
    {
        if (!Options::bool('MusicEnable')) return null;
        $source = (string)Options::get('MusicSource', 'list');
        $cfg = ['source' => $source, 'autoplay' => Options::bool('MusicAutoplay'), 'list' => [], 'meting' => null];
        if ($source === 'meting') {
            $api = self::safeUrl((string)Options::get('MusicMetingApi', ''));
            $id = preg_replace('/[^0-9A-Za-z_-]/', '', (string)Options::get('MusicMetingId', ''));
            $server = in_array(Options::get('MusicMetingServer'), ['netease', 'tencent', 'kugou', 'kuwo'], true) ? (string)Options::get('MusicMetingServer') : 'netease';
            if ($api === '' || $id === '') return null;
            $cfg['meting'] = str_replace([':server', ':id'], [rawurlencode($server), rawurlencode($id)], $api);
        } else {
            $cfg['list'] = self::parse((string)Options::get('MusicList', ''));
            if (empty($cfg['list'])) return null;
        }
        return $cfg;
    }
}
