<?php
require __DIR__ . '/bootstrap.php';
foreach (glob(__DIR__ . '/unit/*Test.php') as $f) {
    echo basename($f) . "\n";
    require $f;
}
$t = $GLOBALS['__tests'];
echo "\n{$t['pass']} passed, {$t['fail']} failed\n";
exit($t['fail'] > 0 ? 1 : 0);
