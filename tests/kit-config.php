<?php
/** 写入 NimbusKit 配置（测试用）。用法：php tests/kit-config.php <sqlite文件> key=value ... */
$db = new PDO('sqlite:' . $argv[1]);
$row = $db->query("SELECT value FROM typecho_options WHERE name='plugin:NimbusKit' AND user=0")->fetchColumn();
$cfg = $row ? unserialize($row) : [];
if (!is_array($cfg)) $cfg = [];
foreach (array_slice($argv, 2) as $kv) {
    [$k, $v] = explode('=', $kv, 2);
    $cfg[$k] = $k === 'features' ? array_values(array_filter(explode(',', $v))) : $v;
}
$val = serialize($cfg);
if ($row === false) $db->prepare("INSERT INTO typecho_options (name,user,value) VALUES ('plugin:NimbusKit',0,?)")->execute([$val]);
else $db->prepare("UPDATE typecho_options SET value=? WHERE name='plugin:NimbusKit' AND user=0")->execute([$val]);
echo json_encode($cfg, JSON_UNESCAPED_UNICODE), "\n";
