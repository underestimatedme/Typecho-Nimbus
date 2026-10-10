<?php
declare(strict_types=1);
define('__TYPECHO_ROOT_DIR__', __DIR__ . '/../.dev/typecho');
define('NIMBUS_TESTING', true);
$GLOBALS['__tests'] = ['pass' => 0, 'fail' => 0];
function test(string $name, callable $fn): void
{
    try {
        $fn();
        $GLOBALS['__tests']['pass']++;
        echo "  ok   $name\n";
    } catch (Throwable $e) {
        $GLOBALS['__tests']['fail']++;
        echo "  FAIL $name\n       " . $e->getMessage() . "\n";
    }
}
function assert_eq($expected, $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(($msg ? "$msg: " : '') . 'expected ' . var_export($expected, true) . ' got ' . var_export($actual, true));
    }
}
function assert_true($v, string $msg = ''): void
{
    if ($v !== true) throw new RuntimeException($msg ?: 'expected true');
}
require_once __DIR__ . '/../Nimbus/core/Bootstrap.php';
// NimbusKit 纯逻辑类（Lib/）无 Typecho 依赖，可直接加载
spl_autoload_register(function (string $class): void {
    $p = 'TypechoPlugin\\NimbusKit\\Lib\\';
    if (strpos($class, $p) !== 0) return;
    $f = __DIR__ . '/../NimbusKit/Lib/' . substr($class, strlen($p)) . '.php';
    if (is_file($f)) require $f;
});
