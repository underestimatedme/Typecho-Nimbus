<?php
/**
 * 开发站媒体数据：生成 3 首合成音轨（WAV）、LRC 歌词与 SVG 封面，并开启主题音乐播放器（幂等）。
 * 用法：php tests/seed-media.php <站点目录>
 */
$site = rtrim($argv[1] ?? '', '/');
if ($site === '' || !is_file($site . '/usr/nimbus.db')) { fwrite(STDERR, "usage: seed-media.php <site dir>\n"); exit(1); }
$dir = $site . '/usr/uploads/nimbus-music';
@mkdir($dir, 0777, true);

function wav(string $file, array $notes, float $beat): void {
    $rate = 11025; $pcm = '';
    foreach ($notes as $n) {
        $len = (int)($rate * $beat);
        for ($i = 0; $i < $len; $i++) {
            $env = min(1, $i / 300) * max(0, 1 - $i / $len) ** 0.6;
            $v = $n > 0 ? (sin(2 * M_PI * $n * $i / $rate) * 0.6 + sin(4 * M_PI * $n * $i / $rate) * 0.15) * $env : 0;
            $pcm .= chr((int)(128 + 90 * $v));
        }
    }
    $h = 'RIFF' . pack('V', 36 + strlen($pcm)) . 'WAVEfmt ' . pack('VvvVVvv', 16, 1, 1, $rate, $rate, 1, 8) . 'data' . pack('V', strlen($pcm));
    file_put_contents($file, $h . $pcm);
}
$hz = function (string $s): array {
    $map = ['C' => 261.6, 'D' => 293.7, 'E' => 329.6, 'F' => 349.2, 'G' => 392.0, 'A' => 440.0, 'B' => 493.9, 'c' => 523.3, 'd' => 587.3, 'e' => 659.3, '-' => 0];
    return array_map(function ($c) use ($map) { return $map[$c]; }, str_split($s));
};
$tracks = [
    ['slug' => 'morning', 'title' => '晴空序曲', 'artist' => 'Nimbus Synth', 'h1' => '#60a5fa', 'h2' => '#c084fc', 'notes' => 'CEGcGECEFAcAFDGBdBGEc-', 'beat' => 0.42,
     'lrc' => ['云层慢慢散开', '风把光送到窗前', '今天也写一点字', '把想法排成句子', '晴空 Nimbus'], 'gap' => 1.6],
    ['slug' => 'dusk', 'title' => '暮色慢板', 'artist' => 'Nimbus Synth', 'h1' => '#f59e0b', 'h2' => '#7c3aed', 'notes' => 'AEcBAGEFGAEDCDE-', 'beat' => 0.6,
     'lrc' => ['日落之后', '灯一盏一盏亮起', '代码还在编译', '我们慢一点走'], 'gap' => 2.2],
    ['slug' => 'neon', 'title' => 'Neon Drive', 'artist' => 'Grid Runner', 'h1' => '#22d3ee', 'h2' => '#f0abfc', 'notes' => 'EEeEDDdDCCcCDDdD', 'beat' => 0.3, 'lrc' => [], 'gap' => 0],
];
$lines = [];
foreach ($tracks as $t) {
    wav("$dir/{$t['slug']}.wav", $hz($t['notes']), $t['beat']);
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="' . $t['h1'] . '"/><stop offset="1" stop-color="' . $t['h2'] . '"/></linearGradient></defs><rect width="300" height="300" fill="url(#g)"/><circle cx="220" cy="80" r="60" fill="#fff" opacity=".18"/><circle cx="70" cy="240" r="90" fill="#000" opacity=".12"/></svg>';
    file_put_contents("$dir/{$t['slug']}.svg", $svg);
    $lrcUrl = '';
    if ($t['lrc']) {
        $out = "[ti:{$t['title']}]\n[ar:{$t['artist']}]\n";
        foreach ($t['lrc'] as $k => $l) { $s = 0.5 + $k * $t['gap']; $out .= sprintf("[%02d:%05.2f]%s\n", (int)($s / 60), fmod($s, 60), $l); }
        file_put_contents("$dir/{$t['slug']}.lrc", $out);
        $lrcUrl = "/usr/uploads/nimbus-music/{$t['slug']}.lrc";
    }
    $lines[] = implode('|', [$t['title'], $t['artist'], "/usr/uploads/nimbus-music/{$t['slug']}.wav", "/usr/uploads/nimbus-music/{$t['slug']}.svg", $lrcUrl]);
}
$list = implode("\n", $lines);

$db = new PDO('sqlite:' . $site . '/usr/nimbus.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$set = ['MusicEnable' => '1', 'MusicSource' => 'list', 'MusicList' => $list];
$row = $db->query("SELECT value FROM typecho_options WHERE name='theme:Nimbus' AND user=0")->fetchColumn();
if ($row !== false) {
    $arr = @unserialize($row);
    if (is_array($arr)) {
        $arr = array_merge($arr, $set);
        $db->prepare("UPDATE typecho_options SET value=? WHERE name='theme:Nimbus' AND user=0")->execute([serialize($arr)]);
    }
}
foreach ($set as $k => $v) {
    $st = $db->prepare("SELECT COUNT(*) FROM typecho_options WHERE name=? AND user=0"); $st->execute([$k]);
    if ($st->fetchColumn()) $db->prepare("UPDATE typecho_options SET value=? WHERE name=? AND user=0")->execute([$v, $k]);
    else $db->prepare("INSERT INTO typecho_options (name,user,value) VALUES (?,0,?)")->execute([$k, $v]);
}
echo "seed-media: " . count($tracks) . " tracks in $dir\n";
