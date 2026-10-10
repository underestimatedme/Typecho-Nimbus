<?php
/** 修改测试站主题设置（测试用）。用法：php tests/set-theme-opt.php <sqlite文件> key=value ... */
$db = new PDO('sqlite:' . $argv[1]);
$row = $db->query("SELECT value FROM typecho_options WHERE name='theme:Nimbus' AND user=0")->fetchColumn();
$a = $row ? unserialize($row) : [];
foreach (array_slice($argv, 2) as $kv) {
    [$k, $v] = explode('=', $kv, 2);
    $a[$k] = $v;
    $st = $db->prepare("SELECT COUNT(*) FROM typecho_options WHERE name=? AND user=0"); $st->execute([$k]);
    if ($st->fetchColumn()) $db->prepare("UPDATE typecho_options SET value=? WHERE name=? AND user=0")->execute([$v, $k]);
    else $db->prepare("INSERT INTO typecho_options (name,user,value) VALUES (?,0,?)")->execute([$k, $v]);
}
if ($row !== false) $db->prepare("UPDATE typecho_options SET value=? WHERE name='theme:Nimbus' AND user=0")->execute([serialize($a)]);
echo "ok\n";
