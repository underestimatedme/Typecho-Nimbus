<?php
/** 重新生成 Nimbus/assets/css/nimbus.bundle.css（修改 CSS 源文件后运行；主题运行时也会自动重建）。 */
define('__TYPECHO_ROOT_DIR__', __DIR__);
define('NIMBUS_TESTING', true);
require __DIR__ . '/../Nimbus/core/Bootstrap.php';
$dir = __DIR__ . '/../Nimbus/assets/css';
$css = \Nimbus\Assets::bundle((string)file_get_contents($dir . '/nimbus.css'), function ($f) use ($dir) { return (string)file_get_contents($dir . '/' . $f); });
file_put_contents($dir . '/nimbus.bundle.css', $css);
echo "nimbus.bundle.css: " . strlen($css) . " bytes\n";
