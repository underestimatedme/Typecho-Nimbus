<?php
/** 重置本地测试站管理员密码为 admin123（幂等）。用法：php tests/reset-admin.php <sqlite文件> */
define('__TYPECHO_ROOT_DIR__', __DIR__ . '/../.dev/typecho');
require __TYPECHO_ROOT_DIR__ . '/var/Typecho/Common.php';
Typecho\Common::init();
$db = new PDO('sqlite:' . $argv[1]);
$db->prepare("UPDATE typecho_users SET password=? WHERE name='admin'")->execute([Typecho\Common::hash('admin123')]);
echo "admin password reset\n";
