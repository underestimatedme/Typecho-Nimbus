# Nimbus 主题第 1 期 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 交付可直接上传使用的 Typecho 主题 Nimbus 1.0：三套皮肤、首页 / 文章 / 评论 / 归档 / 友链 / 404 全套模板、后台配置页、零构建。

**Architecture:** PHP 侧 `functions.php` 只做入口，逻辑在 `core/` 命名空间 `Nimbus\` 的静态类里，模板由 `partials/` 片段组装。前端为 CSS 变量皮肤 + 原生 ES Modules，按页面懒加载，第三方库本地化。NimbusKit 插件通过 `Kit` 适配层接入，缺失时降级。

**Tech Stack:** PHP 7.4+（语法限制到 7.4）、Typecho 1.2.1、原生 CSS（嵌套 / @layer / color-mix）、ES Modules、Prism、KaTeX、Mermaid、SQLite 本地测试站。

**Spec:** `docs/superpowers/specs/2026-10-09-nimbus-theme-phase1-design.md`

## Global Constraints

- PHP 7.4 – 8.4 兼容：禁用 `match`、`enum`、命名参数、`readonly`、联合类型、`str_contains` 等 8.0+ 特性（由 `tests/lint-php74.sh` 检查）。
- Typecho 1.2.1+；使用 `Typecho_Widget_Helper_Form_Element_*`、`Helper::options()`、`Typecho_Db::get()`。
- 零构建、零 Composer、零 CDN 依赖；第三方库放 `Nimbus/assets/vendor/<lib>/` 并附 LICENSE。
- 不复用 Cuteen 任何源码。
- 主题目录名固定 `Nimbus`；PHP 命名空间 `Nimbus\`；CSS 类名前缀 `n-`；JS 自定义事件前缀 `nimbus:`；`localStorage` 键前缀 `nimbus-`。
- 皮肤 ID 固定：`sky`、`dusk`、`cyber`。
- 所有输出通过 `Nimbus\View::e()` 转义，除正文 / 自定义代码 / 小部件 HTML。
- 每个任务结束前运行：`bash tests/lint.sh`（php -l + php74 lint）、`php tests/run.php`（单测）、`bash tests/smoke.sh`（需本地站，Task 1 之后可用）。
- 提交信息用中文，格式 `type: 描述`，结尾带 `Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>`。

## Review Focus

1. 配置项被用户清空（空字符串）时必须回落默认值而不是输出空 → Task 1 的 `Options::get` 测试覆盖空串。
2. 文章无正文图片、无自定义 thumb、无默认图池时，卡片必须仍可渲染（显示纯色占位）→ Task 2 的 `Post::thumb` 测试覆盖三者全空。
3. 置顶 cid 列表包含不存在 / 草稿 / 全角逗号分隔的值时不能产生空轮播页 → Task 5 测试 `Options::list` 的分隔符解析与 `Post::byCids` 过滤。
4. 自定义菜单 JSON 非法时导航必须回落到 Typecho 页面列表而不是白屏 → Task 2 的 `Menu::build` 测试覆盖非法 JSON。
5. 无刷新跳转遇到外链 / 下载 / 后台 / 锚点 / 404 / 非 HTML 响应时必须整页跳转或正确显示 → Task 11 的 router 判定函数单测（Node 跑纯函数）。

---

### Task 1: 仓库脚手架、Bootstrap、Options、后台配置、测试基础设施

**Files:**
- Create: `Nimbus/functions.php`
- Create: `Nimbus/core/Bootstrap.php`
- Create: `Nimbus/core/Options.php`
- Create: `Nimbus/core/Config/Site.php`, `Index.php`, `Post.php`, `Sidebar.php`, `Appearance.php`, `Advanced.php`, `Fields.php`
- Create: `Nimbus/core/Admin.php`
- Create: `Nimbus/assets/css/admin.css`, `Nimbus/assets/js/admin.js`
- Create: `Nimbus/index.php`（临时最小版，Task 6 替换）
- Create: `tests/run.php`, `tests/bootstrap.php`, `tests/unit/OptionsTest.php`, `tests/lint.sh`, `tests/lint-php74.sh`, `tests/smoke.sh`, `tests/dev-site.sh`
- Create: `LICENSE`（MIT）

**Interfaces:**
- Produces: `Nimbus\Options::get(string $key, $default = null)`, `Options::bool(string $key): bool`, `Options::int(string $key): int`, `Options::list(string $key): array`（按换行 / 半角逗号 / 全角逗号 / 顿号 / 分号拆分，trim，去空去重）、`Options::json(string $key): ?array`、`Options::setSource(callable $fn)`（测试注入，签名 `fn(string $key): ?string`）。
- Produces: 每个 `Nimbus\Config\*` 类有 `public static function items(): array`，元素形如 `['name'=>'HeroEnable','type'=>'radio','label'=>'...','description'=>'...','default'=>'1','options'=>['1'=>'开启','0'=>'关闭']]`；`type` ∈ `text|textarea|radio|checkbox|select`。`Config\Fields::render(Typecho_Widget_Helper_Layout $layout)`。
- Produces: `Nimbus\Admin::render(Typecho_Widget_Helper_Form $form)`、`Nimbus\Bootstrap::init($archive)`、常量 `NIMBUS_VERSION`、`NIMBUS_DIR`。
- Produces: 测试工具 `tests/bootstrap.php` 定义 `assert_eq($a,$b,$msg)`、`assert_true($v,$msg)`、`test(string $name, callable $fn)`；`php tests/run.php` 加载 `tests/unit/*Test.php`，非零退出表示失败。

- [ ] **Step 1: 写测试基础设施与 Options 失败测试**

`tests/bootstrap.php`：

```php
<?php
declare(strict_types=1);
define('__TYPECHO_ROOT_DIR__', __DIR__ . '/../.dev/typecho');
define('NIMBUS_TESTING', true);
$GLOBALS['__tests'] = ['pass' => 0, 'fail' => 0];
function test(string $name, callable $fn): void {
    try { $fn(); $GLOBALS['__tests']['pass']++; echo "  ok   $name\n"; }
    catch (Throwable $e) { $GLOBALS['__tests']['fail']++; echo "  FAIL $name\n       " . $e->getMessage() . "\n"; }
}
function assert_eq($expected, $actual, string $msg = ''): void {
    if ($expected !== $actual) throw new RuntimeException(($msg ? "$msg: " : '') . 'expected ' . var_export($expected, true) . ' got ' . var_export($actual, true));
}
function assert_true($v, string $msg = ''): void { if ($v !== true) throw new RuntimeException($msg ?: 'expected true'); }
require_once __DIR__ . '/../Nimbus/core/Bootstrap.php';
```

`tests/run.php`：

```php
<?php
require __DIR__ . '/bootstrap.php';
foreach (glob(__DIR__ . '/unit/*Test.php') as $f) { echo basename($f) . "\n"; require $f; }
$t = $GLOBALS['__tests'];
echo "\n{$t['pass']} passed, {$t['fail']} failed\n";
exit($t['fail'] > 0 ? 1 : 0);
```

`tests/unit/OptionsTest.php`：

```php
<?php
use Nimbus\Options;
$store = [];
Options::setSource(function (string $k) use (&$store) { return $store[$k] ?? null; });
test('get returns stored value', function () use (&$store) { $store['SiteSubtitle'] = 'hi'; assert_eq('hi', Options::get('SiteSubtitle')); });
test('get falls back to default when empty string', function () use (&$store) { $store['SiteSubtitle'] = ''; assert_eq('x', Options::get('SiteSubtitle', 'x')); });
test('get falls back to config default when missing', function () use (&$store) { unset($store['ListStyle']); assert_eq('card', Options::get('ListStyle')); });
test('bool parses 1/0', function () use (&$store) { $store['HeroEnable'] = '0'; assert_eq(false, Options::bool('HeroEnable')); $store['HeroEnable'] = '1'; assert_eq(true, Options::bool('HeroEnable')); });
test('int parses', function () use (&$store) { $store['RelatedCount'] = '7'; assert_eq(7, Options::int('RelatedCount')); $store['RelatedCount'] = 'abc'; assert_eq(4, Options::int('RelatedCount')); });
test('list splits on newline, comma, fullwidth comma, 、 and ;', function () use (&$store) { $store['TopPosts'] = "1, 2，3、4;5\n6\n\n6"; assert_eq(['1','2','3','4','5','6'], Options::list('TopPosts')); });
test('json returns null on invalid', function () use (&$store) { $store['CustomMenu'] = '{bad'; assert_eq(null, Options::json('CustomMenu')); $store['CustomMenu'] = '[{"a":1}]'; assert_eq([['a'=>1]], Options::json('CustomMenu')); });
```

- [ ] **Step 2: 运行测试确认失败**

Run: `cd /opt/coding/planb/github/Nimbus && php tests/run.php`
Expected: 报错 Bootstrap.php 不存在。

- [ ] **Step 3: 写 Bootstrap 与 Options**

`Nimbus/core/Bootstrap.php`：

```php
<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
define('NIMBUS_VERSION', '1.0.0');
define('NIMBUS_DIR', dirname(__DIR__));
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    exit('<h2>Nimbus 需要 PHP 7.4 或更高版本，当前为 ' . PHP_VERSION . '</h2>');
}
spl_autoload_register(function (string $class): void {
    if (strpos($class, 'Nimbus\\') !== 0) return;
    $file = NIMBUS_DIR . '/core/' . str_replace('\\', '/', substr($class, 7)) . '.php';
    if (is_file($file)) require $file;
});
namespace_placeholder:
```

（上面最后一行是说明占位，实际文件不含它；Bootstrap 不用命名空间。）再加：

```php
class NimbusBootstrap
{
    public static function init($archive): void
    {
        $o = \Helper::options();
        $o->commentsThreaded = true;
        $o->commentsMaxNestingLevels = max(3, (int)$o->commentsMaxNestingLevels);
        $o->commentsPageDisplay = 'last';
        $o->commentsOrder = 'ASC';
    }
}
```

`Nimbus/core/Options.php`：

```php
<?php
namespace Nimbus;

final class Options
{
    /** @var callable|null */
    private static $source = null;
    private static ?array $defaults = null;

    public static function setSource(callable $fn): void { self::$source = $fn; self::$defaults = null; }

    private static function raw(string $key): ?string
    {
        if (self::$source) { $v = (self::$source)($key); return $v === null ? null : (string)$v; }
        $o = \Helper::options();
        $v = $o->{$key};
        if (is_array($v)) return implode(',', $v);
        return $v === null ? null : (string)$v;
    }

    private static function defaults(): array
    {
        if (self::$defaults !== null) return self::$defaults;
        $d = [];
        foreach (['Site','Index','Post','Sidebar','Appearance','Advanced'] as $g) {
            $cls = 'Nimbus\\Config\\' . $g;
            foreach ($cls::items() as $it) $d[$it['name']] = $it['default'] ?? '';
        }
        return self::$defaults = $d;
    }

    public static function get(string $key, $default = null)
    {
        $v = self::raw($key);
        if ($v === null || trim($v) === '') return $default ?? (self::defaults()[$key] ?? null);
        return $v;
    }
    public static function bool(string $key): bool { return in_array((string)self::get($key), ['1','true','on'], true); }
    public static function int(string $key): int { $v = self::get($key); return is_numeric($v) ? (int)$v : (int)(self::defaults()[$key] ?? 0); }
    public static function list(string $key): array
    {
        $v = (string)self::get($key, '');
        $parts = preg_split('/[\n,，、;；]+/u', $v) ?: [];
        $out = [];
        foreach ($parts as $p) { $p = trim($p); if ($p !== '' && !in_array($p, $out, true)) $out[] = $p; }
        return $out;
    }
    public static function json(string $key): ?array
    {
        $v = self::get($key); if (!is_string($v)) return null;
        $d = json_decode($v, true);
        return is_array($d) ? $d : null;
    }
}
```

- [ ] **Step 4: 写六个 Config 分组与 Fields**

每个分组类结构相同，示例 `Nimbus/core/Config/Index.php`（其余分组按 spec 3.1 列出的项逐一声明，`default` 必填）：

```php
<?php
namespace Nimbus\Config;

final class Index
{
    public static function title(): string { return '首页'; }
    public static function items(): array
    {
        return [
            ['name'=>'HeroEnable','type'=>'radio','label'=>'首页 Hero 区','description'=>'关闭后首页从导航下方直接显示文章列表。','default'=>'1','options'=>['1'=>'开启','0'=>'关闭']],
            ['name'=>'HeroImages','type'=>'textarea','label'=>'Hero 背景图','description'=>'每行一个图片 URL，多行时随机展示。留空使用主题内置图。','default'=>''],
            ['name'=>'HeroHeight','type'=>'text','label'=>'Hero 高度','description'=>'CSS 高度值，如 600px 或 70vh。','default'=>'600px'],
            ['name'=>'HeroTitle','type'=>'text','label'=>'Hero 主标题','description'=>'留空使用站点名称。用 | 分隔前后两段，后段显示流光效果，如：给时光|以生命','default'=>''],
            ['name'=>'HeroSubtitles','type'=>'textarea','label'=>'Hero 副标题','description'=>'每行一句，打字机效果轮播。','default'=>"记录、思考、与世界温柔相处"],
            ['name'=>'HeroKicker','type'=>'text','label'=>'Hero 状态胶囊文字','description'=>'留空不显示。','default'=>''],
            ['name'=>'TopPosts','type'=>'text','label'=>'置顶文章','description'=>'文章 cid，用逗号分隔。留空不显示轮播。','default'=>''],
            ['name'=>'ListStyle','type'=>'radio','label'=>'列表样式','description'=>'','default'=>'card','options'=>['card'=>'图文交错卡片','grid'=>'三栏网格','plain'=>'纯文字列表']],
            ['name'=>'Pagination','type'=>'radio','label'=>'分页方式','description'=>'','default'=>'normal','options'=>['normal'=>'传统分页','more'=>'加载更多按钮','infinite'=>'无限滚动']],
            ['name'=>'ExcerptLength','type'=>'text','label'=>'摘要字数','description'=>'','default'=>'120'],
            ['name'=>'ThumbPool','type'=>'textarea','label'=>'默认缩略图池','description'=>'每行一个 URL。文章无图时按 cid 稳定选取。留空使用主题内置图。','default'=>''],
        ];
    }
}
```

Site 分组项：`LogoImage`、`LogoText`、`Favicon`、`SiteSubtitle`、`ICP`、`Police`、`FooterHtml`、`AuthorAvatar`、`AuthorName`、`AuthorBio`、`SocialLinks`（textarea，每行 `名称|URL|图标名`，图标名 ∈ github/weibo/bilibili/twitter/email/rss/qq/wechat/telegram/zhihu/link）。
Post 分组项：`ShowWordCount`、`ShowReadingTime`、`TocEnable`、`CodeLineNumbers`、`CodeFoldLines`（default 30）、`CopyrightEnable`、`CopyrightText`（default `本文采用 CC BY-NC-SA 4.0 许可协议，转载请注明出处。`）、`RewardEnable`、`RewardAlipay`、`RewardWechat`、`RewardQQ`、`ShareEnable`、`RelatedCount`（default 4）、`ExpireDays`（default 0）、`LightboxEnable`、`MathEnable`（auto/on/off，default auto）、`MermaidEnable`（同）。
Sidebar 分组项：`SidebarPosition`（right/left/none）、`Widgets`（textarea，default `author\nnotice\nhot\nrecent-comments\ntags\ncategories`）、`NoticeHtml`、`HotCount`（default 5）、`CustomWidgetTitle`、`CustomWidgetHtml`。
Appearance 分组项：`ThemeLight`（sky，options sky）、`ThemeDark`（dusk/cyber，default dusk）、`ThemeFollowSystem`（1）、`ThemeSwitchable`（1）、`FontScheme`（system/serif/custom，default serif）、`FontCustomUrl`、`FontCustomFamily`、`Radius`（sm/md/lg，default md）、`Motion`（off/reduced/full，default full）、`Mourning`（0）。
Advanced 分组项：`CdnPrefix`、`HeadCode`、`FootCode`、`CustomCss`、`CustomJs`、`RouterEnable`（1）、`CustomMenu`（JSON）、`GravatarMirror`（default `https://cravatar.cn/avatar/`）、`Debug`（0）。

`Nimbus/core/Config/Fields.php`：

```php
<?php
namespace Nimbus\Config;

final class Fields
{
    public static function render(\Typecho_Widget_Helper_Layout $layout): void
    {
        $f = [
            ['thumb','text','缩略图 URL','留空自动提取正文首图。'],
            ['summary','textarea','自定义摘要','留空自动截取。'],
            ['expireNotice','text','过期提醒天数','覆盖全局设置，0 关闭，留空用全局。'],
            ['passwordHint','text','密码提示语','仅密码保护文章显示。'],
            ['links','textarea','友链数据（仅友链页）','JSON 数组：[{"name":"","url":"","avatar":"","desc":""}]'],
        ];
        foreach ($f as [$name,$type,$label,$desc]) {
            $cls = $type === 'textarea' ? '\Typecho_Widget_Helper_Form_Element_Textarea' : '\Typecho_Widget_Helper_Form_Element_Text';
            $el = new $cls($name, null, '', $label, $desc);
            $layout->addItem($el);
        }
        $cb = new \Typecho_Widget_Helper_Form_Element_Checkbox('nimbusHide', ['title'=>'隐藏标题','toc'=>'隐藏目录','copyright'=>'隐藏版权','reward'=>'隐藏打赏'], [], '显示控制', '');
        $layout->addItem($cb);
    }
}
```

- [ ] **Step 5: 写 Admin 渲染、functions.php、admin.css/js**

`Nimbus/core/Admin.php`：

```php
<?php
namespace Nimbus;

final class Admin
{
    private const GROUPS = ['Site','Index','Post','Sidebar','Appearance','Advanced'];

    public static function render(\Typecho_Widget_Helper_Form $form): void
    {
        $url = \Helper::options()->themeUrl . '/assets';
        echo '<link rel="stylesheet" href="' . View::e($url) . '/css/admin.css?v=' . NIMBUS_VERSION . '">';
        echo '<div class="n-admin" data-nimbus-admin><div class="n-admin-head"><strong>Nimbus</strong> <span>v' . NIMBUS_VERSION . '</span>';
        if (!Kit::enabled()) echo '<span class="n-admin-tip">未检测到 NimbusKit 插件：点赞、浏览量、即时搜索等功能将自动隐藏。</span>';
        echo '</div><nav class="n-admin-tabs">';
        foreach (self::GROUPS as $g) { $cls = 'Nimbus\\Config\\' . $g; echo '<button type="button" data-tab="' . $g . '">' . View::e($cls::title()) . '</button>'; }
        echo '</nav></div>';
        foreach (self::GROUPS as $g) {
            $cls = 'Nimbus\\Config\\' . $g;
            foreach ($cls::items() as $it) {
                $el = self::element($it);
                $el->setAttribute('class', 'n-admin-item n-tab-' . $g);
                $form->addInput($el);
            }
        }
        echo '<script src="' . View::e($url) . '/js/admin.js?v=' . NIMBUS_VERSION . '"></script>';
    }

    private static function element(array $it)
    {
        $opts = $it['options'] ?? null;
        switch ($it['type']) {
            case 'textarea': return new \Typecho_Widget_Helper_Form_Element_Textarea($it['name'], null, $it['default'], $it['label'], $it['description']);
            case 'radio': return new \Typecho_Widget_Helper_Form_Element_Radio($it['name'], $opts, $it['default'], $it['label'], $it['description']);
            case 'select': return new \Typecho_Widget_Helper_Form_Element_Select($it['name'], $opts, $it['default'], $it['label'], $it['description']);
            case 'checkbox': return new \Typecho_Widget_Helper_Form_Element_Checkbox($it['name'], $opts, $it['default'], $it['label'], $it['description']);
            default: return new \Typecho_Widget_Helper_Form_Element_Text($it['name'], null, $it['default'], $it['label'], $it['description']);
        }
    }
}
```

`Nimbus/functions.php`：

```php
<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
require_once __DIR__ . '/core/Bootstrap.php';
function themeConfig($form) { \Nimbus\Admin::render($form); }
function themeFields($layout) { \Nimbus\Config\Fields::render($layout); }
function themeInit($archive) { NimbusBootstrap::init($archive); }
```

`admin.js`：找到 `[data-nimbus-admin]`，把每个 `.n-admin-item` 的最近 `li` / `.typecho-option` 父元素按 `n-tab-*` 类分组；点击 tab 只显示对应组；当前 tab 存 `localStorage['nimbus-admin-tab']`；默认第一个。`admin.css`：tab 条样式（胶囊按钮、激活态蓝底）、提示条样式、隐藏非当前组 `.n-hidden{display:none}`。

`View` 与 `Kit` 在 Task 2 实现；本任务先创建最小版：`View::e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }`，`Kit::enabled(): bool { return false; }`（Task 2 完整实现）。

临时 `Nimbus/index.php`：

```php
<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Nimbus — 开源免费的 Typecho 主题
 * @package Nimbus
 * @author Nimbus Contributors
 * @version 1.0.0
 * @link https://github.com/underestimatedme/Nimbus
 */
?><!DOCTYPE html><html lang="zh-CN" data-theme="sky"><head><meta charset="utf-8"><title><?php $this->options->title(); ?></title></head><body data-page="index"><?php while ($this->next()): ?><h2><?php $this->title(); ?></h2><?php endwhile; ?></body></html>
```

- [ ] **Step 6: 运行单测确认通过**

Run: `php tests/run.php`
Expected: 7 passed, 0 failed。

- [ ] **Step 7: 写 lint 脚本并运行**

`tests/lint.sh`：

```bash
#!/usr/bin/env bash
set -e
cd "$(dirname "$0")/.."
fail=0
while IFS= read -r f; do php -l "$f" >/dev/null || fail=1; done < <(find Nimbus tests -name '*.php')
bash tests/lint-php74.sh || fail=1
[ $fail -eq 0 ] && echo "lint ok"
exit $fail
```

`tests/lint-php74.sh`：

```bash
#!/usr/bin/env bash
cd "$(dirname "$0")/.."
bad=0
pat='\bmatch\s*\(|\benum\s+[A-Z]|\breadonly\b|\bstr_contains\(|\bstr_starts_with\(|\bstr_ends_with\(|:\s*(int|string|array|bool|float|\?[a-z]+)\|[a-z]|\bnever\b|\$[a-z]+\s*:\s*[^=]*=>|\(\s*[a-z]+:\s'
if grep -rnE "$pat" Nimbus --include='*.php' | grep -v '^\s*//' ; then echo "PHP 8+ syntax found"; bad=1; fi
exit $bad
```

Run: `bash tests/lint.sh` → `lint ok`。

- [ ] **Step 8: 本地测试站脚本与冒烟脚本**

`tests/dev-site.sh`（幂等）：若 `.dev/typecho/config.inc.php` 不存在，则：用 `php -S 127.0.0.1:8099 -t .dev/typecho` 后台启动；`curl` POST `install.php?step=2`（参数：`dbAdapter=Pdo_SQLite`、`dbFile=<abs>/.dev/typecho/usr/nimbus.db`、`dbPrefix=typecho_`）；再 POST `install.php?step=3`（`userUrl=http://127.0.0.1:8099`、`userName=admin`、`userPassword=admin123`、`userMail=admin@example.com`）；若 Typecho 1.2.1 的安装表单字段名不同，以 `.dev/typecho/install.php` 源码为准调整。然后 `ln -sfn <repo>/Nimbus .dev/typecho/usr/themes/Nimbus`，用 PHP + PDO 把 `typecho_options` 表 `name='theme'` 的 `value` 更新为 `Nimbus`，并插入测试数据：3 个分类、6 个标签、15 篇文章（含代码块、公式、mermaid、图片、长文、密码保护各一篇，其余普通）、3 个独立页（archives / links / about，slug 对应模板）、每篇 2–5 条评论（含嵌套）、2 篇置顶备用。脚本打印站点 URL。

`tests/smoke.sh`：启动（或复用）`php -S 127.0.0.1:8099`，对 `/`、`/index.php/archives/1/`、`/index.php/category/default/`、`/index.php/archives.html`、`/index.php/search/test/`、`/index.php/not-exist/` 逐个 curl，断言：HTTP 状态（最后一个 404，其余 200）、响应体不含 `Warning:` / `Notice:` / `Fatal error` / `Deprecated:`、含 `data-page=`。输出 `smoke ok` 或失败明细。

Run: `bash tests/dev-site.sh && bash tests/smoke.sh` → `smoke ok`（此时用的是临时 index.php，其它模板回落到 index.php 也可）。

- [ ] **Step 9: 提交**

```bash
git add -A && git commit -m "feat: 主题脚手架、配置系统与测试基础设施"
```

---

### Task 2: View、Post、Menu、Kit、Seo 工具类

**Files:**
- Create: `Nimbus/core/View.php`, `Nimbus/core/Post.php`, `Nimbus/core/Menu.php`, `Nimbus/core/Kit.php`, `Nimbus/core/Seo.php`
- Create: `Nimbus/assets/img/thumbs/01.jpg … 06.jpg`（主题内置缩略图池，用 CSS 渐变 SVG 代替 JPG：`thumb-01.svg … thumb-06.svg`，每个是 1200×800 的渐变 + 柔和形状，三套皮肤都协调）
- Test: `tests/unit/PostTest.php`, `tests/unit/MenuTest.php`, `tests/unit/ViewTest.php`

**Interfaces:**
- Consumes: `Options`。
- Produces：
  - `View::e(?string): string`；`View::partial(string $name, array $vars = []): void`（include `NIMBUS_DIR/partials/$name.php`，`$vars` extract；若传 `$this` 用键 `archive`）；`View::asset(string $path): string`（`Options::get('CdnPrefix')` 或 `themeUrl . '/assets'` + `/path?v=NIMBUS_VERSION`）；`View::icon(string $name, string $class = ''): string`（返回内联 SVG，图标集 `search moon sun menu close arrow-right arrow-up eye comment heart clock tag folder calendar link github weibo bilibili twitter email rss qq wechat telegram zhihu star copy check chevron-down share external lock`，存于 `View::$icons` 数组）；`View::themeUrl(): string`。
  - `Post::thumb(\Widget_Abstract_Contents $post): string`（顺序：fields->thumb → 正文首 `<img src>` → `Options::list('ThumbPool')` 按 `cid % n` → 内置 `assets/img/thumbs/thumb-0N.svg` 按 `cid % 6`）；`Post::thumbFrom(?string $field, string $html, array $pool, int $cid, string $builtinBase): string`（纯函数，供测试）；`Post::excerpt(\Widget_Abstract_Contents $post, int $len): string`；`Post::excerptFrom(?string $field, string $html, int $len): string`（去标签、合并空白、按 mb 截断加 `…`）；`Post::wordCount(string $html): int`（中文按字、英文按词）；`Post::readingTime(int $words): int`（分钟，`max(1, ceil($words/400))`）；`Post::adjacent(\Widget_Archive $a): array{prev:?array,next:?array}`（各含 cid/title/permalink/thumb）；`Post::related(\Widget_Archive $a, int $limit): array`（同标签优先，补同分类，排除自身，发布状态）；`Post::byCids(array $cids): array`（仅 `type='post' AND status='publish'`，保持传入顺序）；`Post::hot(int $limit): array`（有 Kit 用 `Kit::hotPosts`，否则按 `commentsNum DESC`）。
  - `Menu::build(array $pages, ?array $custom): array`（返回 `[['title','url','icon','children'=>[]]…]`；`$custom` 非空且合法则用它，每项 `{title,url,icon?,children?}`；否则由 `$pages`（每项 `title,permalink`）生成）；`Menu::items(): array`（从 Typecho 页面 Widget 与 `Options::json('CustomMenu')` 组装，首项固定"首页"）。
  - `Kit::enabled(): bool`（检查 `Typecho_Plugin::export()['activated']['NimbusKit']` 且 `class_exists('NimbusKit_Plugin')`）；`Kit::apiUrl(): ?string`（`Helper::options()->index . '/nimbus-api'`）；`Kit::views(int): ?int`、`Kit::likes(int): ?int`、`Kit::hotPosts(int): ?array`、`Kit::links(): ?array`（第 1 期均在未启用时返回 null；启用时调用 `NimbusKit_Api::*` 若存在，否则 null）。
  - `Seo::head(\Widget_Archive $a): string`（输出 description、OG、twitter、canonical、JSON-LD）。

- [ ] **Step 1: 写失败测试**

`tests/unit/PostTest.php`：

```php
<?php
use Nimbus\Post;
test('thumbFrom prefers field', fn() => assert_eq('https://a/b.jpg', Post::thumbFrom('https://a/b.jpg', '<img src="x.jpg">', [], 1, '/t')));
test('thumbFrom extracts first img', fn() => assert_eq('x.jpg', Post::thumbFrom('', '<p>hi</p><img alt="" src="x.jpg"><img src="y.jpg">', [], 1, '/t')));
test('thumbFrom uses pool by cid mod', fn() => assert_eq('b', Post::thumbFrom(null, '', ['a','b','c'], 4, '/t')));
test('thumbFrom falls back to builtin', fn() => assert_eq('/t/thumb-03.svg', Post::thumbFrom(null, '', [], 8, '/t')));
test('excerptFrom strips and truncates', fn() => assert_eq('你好世界这是…', Post::excerptFrom(null, "<p>你好  世界</p>\n<pre>这是一段很长的代码</pre>", 6)));
test('excerptFrom prefers field', fn() => assert_eq('自定义', Post::excerptFrom('自定义', '<p>abc</p>', 100)));
test('wordCount counts cjk chars and latin words', fn() => assert_eq(7, Post::wordCount('<p>你好，世界 hello world</p>')));
test('readingTime min 1', fn() => assert_eq(1, Post::readingTime(10)));
test('readingTime ceil', fn() => assert_eq(3, Post::readingTime(900)));
```

`tests/unit/MenuTest.php`：

```php
<?php
use Nimbus\Menu;
$pages = [['title'=>'关于','permalink'=>'/about'],['title'=>'友链','permalink'=>'/links']];
test('build from pages', function () use ($pages) { $m = Menu::build($pages, null); assert_eq('关于', $m[0]['title']); assert_eq('/about', $m[0]['url']); assert_eq([], $m[0]['children']); });
test('build from custom json', function () use ($pages) { $m = Menu::build($pages, [['title'=>'A','url'=>'/a','children'=>[['title'=>'B','url'=>'/b']]]]); assert_eq('A', $m[0]['title']); assert_eq('B', $m[0]['children'][0]['title']); });
test('build ignores invalid custom entries and falls back', function () use ($pages) { $m = Menu::build($pages, [['nope'=>1]]); assert_eq('关于', $m[0]['title']); });
```

`tests/unit/ViewTest.php`：

```php
<?php
use Nimbus\View;
test('e escapes', fn() => assert_eq('&lt;a&gt;&quot;', View::e('<a>"')));
test('e null safe', fn() => assert_eq('', View::e(null)));
test('icon returns svg', fn() => assert_true(strpos(View::icon('search', 'n-ico'), '<svg') === 0 && strpos(View::icon('search','n-ico'), 'n-ico') !== false));
test('icon unknown returns empty', fn() => assert_eq('', View::icon('nope')));
```

- [ ] **Step 2: 运行确认失败**

Run: `php tests/run.php` → 多项 FAIL（类不存在）。

- [ ] **Step 3: 实现五个类与内置缩略图**

按 Interfaces 实现。`Post::wordCount` 实现：

```php
public static function wordCount(string $html): int
{
    $t = trim(strip_tags($html));
    $cjk = preg_match_all('/\p{Han}/u', $t);
    $latin = preg_match_all('/[A-Za-z0-9_]+/u', $t);
    return (int)$cjk + (int)$latin;
}
```

`Post::excerptFrom`：`$s = $field !== null && trim($field) !== '' ? trim($field) : preg_replace('/\s+/u', '', strip_tags($html))`；若 `mb_strlen($s) > $len` 返回 `mb_substr($s, 0, $len) . '…'`。注意测试期望空白被移除（中文场景）。

`Post::thumbFrom`：`preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $m)`。

`Post::adjacent` / `related` / `byCids` / `hot` 用 `Typecho_Db::get()`，`select('cid','title','slug','created','type','status','text','categories?')` 由 `\Widget_Abstract_Contents::filter()` 不可直接用，改为 `\Typecho_Widget::widget('Widget_Abstract_Contents')->push($row)` 获得 permalink，或手动用 `\Typecho_Router::url('post', $row, \Helper::options()->index)`（需 `category`/`year/month/day` 字段；用 `Widget_Abstract_Contents::filter` 方式最稳：`$w = \Typecho_Widget::widget('Widget_Abstract_Contents'); $row = $w->filter($row);`）。

内置缩略图：6 个 SVG，`<svg xmlns viewBox="0 0 1200 800"><defs><linearGradient …>` 两色渐变（例如 01 `#60a5fa→#a78bfa`，02 `#f59e0b→#f43f5e`，03 `#34d399→#3b82f6`，04 `#f472b6→#fb923c`，05 `#22d3ee→#6366f1`，06 `#a3e635→#14b8a6`）+ 两个半透明大圆。

- [ ] **Step 4: 运行单测与 lint**

Run: `php tests/run.php && bash tests/lint.sh` → 全部通过。

- [ ] **Step 5: 提交**

```bash
git add -A && git commit -m "feat: View/Post/Menu/Kit/Seo 工具类与内置缩略图"
```

---

### Task 3: CSS 设计系统（令牌、三套皮肤、基础、布局、排版）

**Files:**
- Create: `Nimbus/assets/css/tokens.css`, `theme-sky.css`, `theme-dusk.css`, `theme-cyber.css`, `base.css`, `layout.css`, `typography.css`, `nimbus.css`, `components/button.css`
- Create: `Nimbus/assets/fonts/README.md`（说明字体来源与子集化方式；字体文件在 Task 13 加入）
- Test: `tests/css-vars.sh`（检查三套皮肤文件各自定义了 spec 2.3 列出的全部变量）

**Interfaces:**
- Produces：
  - `nimbus.css` 用 `@layer reset, base, components, pages, utilities;` 声明层序，然后 `@import url(tokens.css) layer(base);` 等依次导入全部文件（皮肤文件也 import，靠 `:root[data-theme=…]` 选择）。
  - `tokens.css`（`:root`）：`--space-1..8`（4/8/12/16/24/32/48/64px）、`--r-sm/--r-md/--r-lg`（由 `html[data-radius=sm|md|lg]` 决定 `--r` = 10/18/26px，默认 md）、`--text-xs..3xl`、`--ease: cubic-bezier(.2,.8,.2,1)`、`--dur-fast .2s`、`--dur .4s`、`--dur-slow .8s`、`--z-nav 150`、`--z-modal 300`、`--z-progress 400`、`--max-w 1180px`、`--side-w 320px`、`--font-sans`（系统栈）、`--font-title`（`"Noto Serif SC","Source Han Serif SC","Songti SC",serif`）、`--font-mono`（`"JetBrains Mono",ui-monospace,SFMono-Regular,Menlo,monospace`）。`html[data-motion=off]` 时 `--dur*` 全为 0s；`html[data-motion=reduced]` 时 `--dur-slow` 为 `.3s` 且 `.n-hero .n-hero-bg`、光斑动画关闭（由 hero.css 读取 `--motion-decor: 0/1`）。
  - 皮肤变量：spec 2.3 列表 + `--code-bg --code-fg --selection`；赛博朋克额外 `--scanline --grid --font-display`。颜色取值与 v4 效果图一致（sky: bg #f4f7fb, card #fff, accent #3b82f6, grad 60a5fa→a78bfa；dusk: bg #0b1220, card #111a2e, accent #f59e0b, grad f59e0b→f43f5e；cyber: bg #05070f, card #0b1024, accent #00f0ff, hover #ff2d95, grad 00f0ff→ff2d95）。
  - `base.css`：现代 reset、`body{background:var(--bg);color:var(--fg);font-family:var(--font-sans)}`、噪点层 `body::before`（opacity .035，fixed，pointer-events none）、`::selection`、`a` 默认样式、`.n-container{max-width:var(--max-w);margin-inline:auto;padding-inline:var(--space-5)}`、`.n-sr-only`、`html[data-mourning] { filter: grayscale(1) }`、`@media (prefers-reduced-motion: reduce)` 关闭动画。
  - `layout.css`：`.n-wrap{display:grid;grid-template-columns:1fr var(--side-w);gap:28px}`，`.n-wrap[data-side=left]` 侧栏在左（`grid-template-columns: var(--side-w) 1fr` + order），`.n-wrap[data-side=none]` 单栏 `max-width: 860px`；`@media (max-width:1000px)` 单栏；`.n-main`、`.n-side`。
  - `typography.css`：`.n-prose` 正文（字号 16.5px/行高 1.85/段距；h2–h4 带左侧 accent 竖线或下划线；blockquote；table 外层 `.n-table-wrap` 横向滚动；img 圆角与 `cursor: zoom-in`；hr；footnote；kbd；mark）。
  - `button.css`：`.n-btn`、`.n-btn-primary`（accent 渐变 + 光晕）、`.n-btn-ghost`、`.n-icon-btn`（圆形 38px，毛玻璃，hover 旋转 20deg 放大）。

- [ ] **Step 1: 写变量检查脚本**

`tests/css-vars.sh`：

```bash
#!/usr/bin/env bash
cd "$(dirname "$0")/.."
vars="bg fg fg-strong fg-soft muted card line line-hover chip glass accent accent-text accent-soft on-accent accent-grad accent-glow orb-a orb-b title-grad shadow shadow-hover hero-overlay code-bg code-fg selection"
bad=0
for t in sky dusk cyber; do
  f=Nimbus/assets/css/theme-$t.css
  for v in $vars; do grep -q -- "--$v:" "$f" || { echo "$f missing --$v"; bad=1; }; done
done
[ $bad -eq 0 ] && echo "css vars ok"; exit $bad
```

Run: `bash tests/css-vars.sh` → 报缺失（文件不存在）。

- [ ] **Step 2: 写全部 CSS 文件**

按 Interfaces 写。皮肤文件结构：

```css
:root[data-theme="sky"] {
  color-scheme: light;
  --bg: #f4f7fb; --fg: #1f2937; --fg-strong: #0f172a; --fg-soft: #4b5563; --muted: #6b7280;
  --card: #fff; --line: rgba(15,23,42,.06); --line-hover: rgba(59,130,246,.35); --chip: #eef2f7; --glass: rgba(255,255,255,.72);
  --accent: #3b82f6; --accent-text: #2563eb; --accent-soft: #dbeafe; --on-accent: #fff;
  --accent-grad: linear-gradient(135deg,#60a5fa,#a78bfa); --accent-glow: rgba(96,165,250,.5);
  --orb-a: #60a5fa; --orb-b: #c084fc; --title-grad: linear-gradient(90deg,#fff,#bfdbfe,#fff);
  --shadow: 0 1px 2px rgba(15,23,42,.04), 0 10px 30px -12px rgba(15,23,42,.12);
  --shadow-hover: 0 24px 50px -16px rgba(59,130,246,.3);
  --hero-overlay: linear-gradient(180deg, rgba(15,23,42,.4), rgba(59,130,246,.18) 60%, rgba(244,247,251,0) 100%);
  --code-bg: #0f172a; --code-fg: #e2e8f0; --selection: #3b82f6;
}
```

dusk / cyber 同构；cyber 额外在 `:root[data-theme="cyber"] body` 上加网格背景 `--grid` 与 `body::after` 扫描线，`--font-display: "Orbitron", var(--font-title)`。

- [ ] **Step 3: 把 nimbus.css 接入临时 index.php 并在浏览器检查**

在临时 `index.php` 的 `<head>` 加 `<link rel="stylesheet" href="<?= \Nimbus\View::asset('css/nimbus.css') ?>">`，`body` 加 `class="n-prose"` 包一段测试内容。Run: `bash tests/css-vars.sh && bash tests/smoke.sh`，再用内置浏览器打开 `http://127.0.0.1:8099/` 分别用 JS 设置 `document.documentElement.dataset.theme='dusk'/'cyber'` 截图确认背景 / 文字颜色变化、控制台无 CSS 解析错误。

- [ ] **Step 4: 提交**

```bash
git add -A && git commit -m "feat: CSS 设计令牌、三套皮肤与基础排版"
```

---

### Task 4: 页面骨架（header / footer / nav / 搜索弹窗）与全局 JS（theme / nav / progress / search / app 入口）

**Files:**
- Create: `Nimbus/partials/header.php`, `footer.php`, `nav.php`, `search-modal.php`
- Create: `Nimbus/assets/css/components/nav.css`, `modal.css`, `footer.css`
- Create: `Nimbus/assets/js/app.js`, `modules/theme.js`, `modules/nav.js`, `modules/progress.js`, `modules/search.js`, `modules/util.js`
- Modify: `Nimbus/index.php`（改用 header/footer partial）
- Test: `tests/smoke.sh` 增加断言：首页含 `data-theme=`、`id="n-search"`、`NimbusConfig`

**Interfaces:**
- Consumes: `View`, `Options`, `Menu`, `Seo`, `Kit`。
- Produces：
  - `header.php` 输出 `<!DOCTYPE html>` 到 `<main id="n-main" class="n-main-wrap">` 之前，含：内联无闪烁脚本（读 `nimbus-theme`，默认按 `Options::get('ThemeLight'|'ThemeDark')` 与 `ThemeFollowSystem`）、`<meta name="theme-color">`、字体 link（`FontScheme`）、`nimbus.css`、`HeadCode`、`CustomCss`、`Seo::head()`、`window.NimbusConfig = {...}`（键：`assets, api, page, cid, themes:{light,dark,follow,switchable}, router, i18n:{copy:'复制',copied:'已复制',loadMore:'加载更多',noMore:'没有更多了',search:'搜索'} , lightbox, codeFold, math, mermaid`）、`<body class="n-page-<page>" data-page="<page>" data-cid="<cid>">`，`data-page` ∈ `index|post|page|archive|error`（由 `$this->is('index')`… 判断，分类 / 标签 / 搜索 / 作者 归为 `index`），导航 partial、顶部进度条 `<div class="n-progress" id="n-progress">`。
  - `nav.php`：绝对定位透明导航（`.n-nav`，Logo、菜单 `Menu::items()` 渲染为 `<ul class="n-menu">` 支持一层子菜单 `<ul class="n-submenu">`、动作按钮搜索 / 主题 / 移动端菜单）+ 悬浮胶囊导航 `.n-nav-float`（同内容，`data-nav-float`）+ 移动端抽屉 `.n-drawer`。无 Hero 的页面（文章页等）`.n-nav` 加 `n-nav-solid` 类直接显示为实色条（不透明背景、文字用 `--fg`），胶囊导航逻辑相同。
  - `search-modal.php`：`<dialog id="n-search" class="n-modal">`，表单 `action="<?= $this->options->index ?>/search/"`，输入框 `name="keywords"`（Typecho 原生搜索路径为 `/search/{keywords}/`，JS 提交时改写为路径）；底部提示 `Esc 关闭`。
  - `footer.php`：`</main>`、页脚（版权、`ICP`、`Police`（带图标链接到 `http://www.beian.gov.cn/portal/registerSystemInfo?recordcode=数字`）、`FooterHtml`、"Powered by Typecho · Theme Nimbus"）、返回顶部按钮 `.n-totop`（带 SVG 进度环，`r=23.5`，周长 148）、`FootCode`、`CustomJs`、`<script type="module" src="app.js">`，`$this->footer()`。
  - JS：`app.js`：`import {init as theme} from './modules/theme.js'` 等；`boot(root=document)` 初始化全局模块，然后 `switch(document.body.dataset.page)` 动态 `import('./pages/' + page + '.js')`（页面模块在后续任务创建；不存在时 catch 忽略）；监听 `nimbus:page` 事件重新 `boot(main)`。`util.js` 导出 `qs, qsa, on, storage(get/set 带 try/catch), prefersReduced(), loadScript(url), loadCss(url)`。`theme.js`：按钮 `[data-theme-toggle]` 点击在 light↔dark 间切换（dark 为 `NimbusConfig.themes.dark`），有 `document.startViewTransition` 时用圆形 clip-path 扩散过渡（`::view-transition-new(root)` 动画 `clip-path: circle(0 at x y) → circle(150% at x y)`，x y 来自点击坐标，写入 CSS 变量），写 `nimbus-theme`，更新 `meta[name=theme-color]` 为皮肤 `--bg`。`nav.js`：滚动超过 Hero 高度（或无 Hero 时 80px）给 `.n-nav-float` 加 `is-show`，`.n-totop` 同；移动端抽屉开关；子菜单 hover/焦点展开；`Esc` 关闭。`progress.js`：顶部进度线宽度 + 返回顶部环 `stroke-dashoffset`。`search.js`：`Cmd/Ctrl+K`、`[data-search-open]` 打开 dialog；提交时 `location.href = index + '/search/' + encodeURIComponent(kw) + '/'`；若 `NimbusConfig.api` 非 null 则预留 `fetch(api + '/search?q=')` 分支渲染结果列表（第 2 期启用）。

- [ ] **Step 1: 扩展 smoke 断言（失败）**

在 `tests/smoke.sh` 首页检查中增加 `grep -q 'data-theme='`、`grep -q 'id="n-search"'`、`grep -q 'NimbusConfig'`。Run → FAIL。

- [ ] **Step 2: 写 partials、CSS、JS，并改 index.php 使用 header/footer**

`index.php` 暂时：header → `<div class="n-container"><div class="n-wrap"><section class="n-main">` 循环输出 `<h2>` → `</section><aside class="n-side"></aside></div></div>` → footer。

nav.css 关键：`.n-nav{position:absolute;inset:0 0 auto;height:72px;z-index:var(--z-nav);color:#fff}`；`.n-nav.n-nav-solid{position:sticky;top:0;background:var(--glass);backdrop-filter:blur(16px);color:var(--fg);border-bottom:1px solid var(--line)}`；`.n-nav-float{position:fixed;top:16px;left:50%;translate:-50% -24px;opacity:0;pointer-events:none;width:min(var(--max-w),calc(100% - 40px));height:56px;border-radius:999px;background:var(--glass);backdrop-filter:blur(18px) saturate(1.4);border:1px solid var(--line);box-shadow:0 10px 40px -12px rgba(0,0,0,.35);transition:translate var(--dur) var(--ease),opacity var(--dur)}`，`.is-show{opacity:1;translate:-50% 0;pointer-events:auto}`；菜单项下划线生长动画（`::after scaleX`）；`.n-drawer`（右侧滑入，`dialog`）。

- [ ] **Step 3: 运行 smoke 与浏览器检查**

Run: `bash tests/smoke.sh` → ok。浏览器：首页顶部导航可见；滚动后胶囊导航滑入；点主题按钮切换 sky↔dusk 有圆形过渡；`Cmd+K` 打开搜索；移动尺寸（375 宽）下抽屉可开关；控制台无错误。

- [ ] **Step 4: 提交**

```bash
git add -A && git commit -m "feat: 页面骨架、导航、搜索弹窗与全局脚本"
```

---

### Task 5: Hero 与置顶轮播

**Files:**
- Create: `Nimbus/partials/hero.php`, `top-carousel.php`
- Create: `Nimbus/assets/css/components/hero.css`, `carousel.css`
- Create: `Nimbus/assets/js/modules/hero.js`, `carousel.js`
- Create: `Nimbus/assets/img/hero-default.jpg`（主题自带一张 1920×1080 的天空 / 云海图，用 SVG 渐变 `hero-default.svg` 代替，避免版权问题）
- Modify: `Nimbus/index.php`
- Test: `tests/unit/PostTest.php` 增加 `byCids` 过滤测试（用 `Options::list` + 纯过滤函数 `Post::filterCids(array $rows, array $cids): array`，保持传入顺序、丢弃不存在）

**Interfaces:**
- Consumes: `Options::list('HeroImages'|'HeroSubtitles'|'TopPosts')`, `Post::byCids`, `Post::thumb`, `View`。
- Produces：`hero.php` 仅首页第一页且 `HeroEnable` 时输出 `<section class="n-hero" style="--hero-h: <HeroHeight>" data-hero><div class="n-hero-bg" style="background-image:url(…)"></div><i class="n-orb n-orb-a"></i><i class="n-orb n-orb-b"></i><div class="n-hero-in"> [kicker] <h1 class="n-hero-title"><span>前</span><em>后</em></h1><p class="n-hero-sub" data-typewriter='["句1","句2"]'><span></span><b class="n-caret"></b></p></div><div class="n-hero-down"></div><svg class="n-hero-wave">…</svg></section>`。`HeroTitle` 用 `|` 分前后段，无 `|` 时整体在 `<span>`；为空用站点名。
- `top-carousel.php`：`$posts = Post::byCids(Options::list('TopPosts'))`，非空时输出 `<div class="n-feature" data-carousel data-interval="5000">` 每篇一个 `<a class="n-slide" href>`（含 `.n-slide-bg`、标签"置顶"、标题、摘要），指示点 `.n-dots > i`，首个 `.is-on`。
- `hero.js`：`init(root)`：打字机（逐字 90ms，删 40ms，停 1500ms，循环）、滚动视差（`translate: 0 (scrollY*.25)px`，仅 `scrollY < hero高度`，`prefersReduced()` 时跳过）；`destroy()` 清定时器。`carousel.js`：`init(root)` 对每个 `[data-carousel]`：定时切换、指示点点击、hover 暂停、触摸左右滑动；进度条通过给当前点加 `.is-on` 并重启动画实现。

- [ ] **Step 1: 写 filterCids 测试（失败）**

```php
test('filterCids keeps order and drops missing', fn() => assert_eq([['cid'=>3],['cid'=>1]], Nimbus\Post::filterCids([['cid'=>1],['cid'=>3],['cid'=>9]], ['3','1','7'])));
```

Run → FAIL。

- [ ] **Step 2: 实现 filterCids、byCids（内部调用 filterCids）、partials、CSS、JS；index.php 接入 hero 与 top-carousel**

hero.css 按 v4：`.n-hero{position:relative;height:var(--hero-h,600px);display:grid;place-items:center;overflow:hidden;color:#fff;text-align:center}`，`.n-hero-bg{position:absolute;inset:-8%;background:center/cover;animation:n-kenburns 24s ease-in-out infinite alternate}`，`.n-hero::after{content:"";position:absolute;inset:0;background:var(--hero-overlay)}`，光斑 `filter:blur(60px);mix-blend-mode:screen;animation:n-drift 16s alternate infinite`，标题 `font:900 clamp(36px,6vw,64px)/1.15 var(--font-title);letter-spacing:.12em`，`em` 用 `--title-grad` 文字渐变流光 `n-shimmer 6s linear infinite`，进场 `n-up` 错落延迟，波浪 `path{fill:var(--bg)}`。cyber 皮肤下 `.n-hero-title{font-family:var(--font-display);text-transform:uppercase}` 与 `em` 故障动画 `n-glitch 4s infinite`。

- [ ] **Step 3: 测试与浏览器检查**

Run: `php tests/run.php && bash tests/lint.sh && bash tests/smoke.sh`。浏览器：Hero 动画、打字机、轮播 5 秒自动切换、三套皮肤各截图一张。

- [ ] **Step 4: 提交**

```bash
git add -A && git commit -m "feat: Hero 区与置顶轮播"
```

---

### Task 6: 文章列表（三种样式）、分页、进场动画、无限滚动

**Files:**
- Create: `Nimbus/partials/post-card.php`, `post-grid.php`, `post-plain.php`, `post-list.php`（根据 `ListStyle` 选择并循环）、`pagination.php`, `list-head.php`（分类 / 标签 / 搜索页标题栏）
- Create: `Nimbus/assets/css/components/card.css`, `pagination.css`
- Create: `Nimbus/assets/js/modules/reveal.js`, `infinite.js`, `pages/index.js`
- Modify: `Nimbus/index.php`（最终版）
- Test: smoke 增加分类页含 `class="n-list"`；单测 `Post::excerptFrom` 已有

**Interfaces:**
- Consumes: `Post::thumb/excerpt/wordCount/readingTime`, `Kit::views`, `View`, `Options`。
- Produces：`post-list.php` 接收 `$archive`（Widget）输出 `<div class="n-list n-list-<style>" id="n-list" data-list>` + 循环对应卡片 partial（每张卡 `<article class="n-card n-reveal" data-cid>`，内含 `.n-card-img > i[style=background-image]`、`.n-card-body`、`.n-meta`（分类 chip、日期）、`h3.n-card-title > a`、`p.n-card-excerpt`、`.n-card-foot`（浏览量（Kit）、评论数、阅读时长、"阅读全文"箭头））；卡片整体可点（标题链接 `::after` 铺满）。`pagination.php`：`normal` 用 `$archive->pageNav('‹','›',2,'…',['wrapTag'=>'nav','wrapClass'=>'n-pagination','itemTag'=>'span','currentClass'=>'is-current'])`；`more` 输出 `<button class="n-btn n-load-more" data-load-more data-next="<下一页URL>">加载更多</button>`；`infinite` 输出同按钮加 `data-infinite`。下一页 URL 由 `$archive->pageLink('', 'next')` 捕获输出得到（用 `ob_start`）。
- `reveal.js`：`IntersectionObserver` 给 `.n-reveal` 加 `.is-in`，按在视口内的序号错落 80ms；`init(root)` 可重复调用。`infinite.js`：点击或滚近按钮时 `fetch(next)`，解析响应 `#n-list` 子节点追加到当前列表，更新 `data-next`（取响应里按钮的 `data-next`），无下一页时按钮文字改 `noMore` 并禁用；追加后调用 `reveal.init` 与派发 `nimbus:append`。`pages/index.js`：`init` 调用 hero、carousel、reveal、infinite。

- [ ] **Step 1: smoke 增加断言（失败）**

分类页检查 `class="n-list`。Run → FAIL。

- [ ] **Step 2: 实现 partials / CSS / JS / index.php**

`index.php` 结构：header → `hero.php`（仅 `$this->is('index') && $this->_currentPage==1`，用 `$this->request->get('page', 1) == 1`）→ `<div class="n-container"><div class="n-wrap" data-side="<SidebarPosition>"><section class="n-main">` `list-head.php`（非首页时显示"分类：xx / 标签：xx / 搜索：xx，共 N 篇"）→ `top-carousel.php`（仅首页第一页）→ `post-list.php` → `pagination.php` `</section>` `sidebar.php`（Task 7，先放空 `<aside class="n-side"></aside>`）`</div></div>` → footer。

card.css 按 v4：交错布局 `.n-list-card .n-card{display:grid;grid-template-columns:300px 1fr}`，`:nth-child(even)` 反转；悬停上浮 / 图片 `scale(1.06)` / 高光扫过（`.n-card-img::after` 斜向渐变从左到右）/ 标题变 accent / 箭头右移；grid 样式 `.n-list-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}`（平板 2 列，手机 1 列），卡片上图下文；plain 样式单行：日期、标题、分类，hover 左侧出现 accent 竖条。

- [ ] **Step 3: 测试与浏览器检查**

Run 全部脚本。浏览器：三种列表样式（通过 PHP 直接改 options 表切换或在后台切换）、分页三模式（加载更多可追加）、移动端单列。

- [ ] **Step 4: 提交**

```bash
git add -A && git commit -m "feat: 文章列表三种样式、分页与无限滚动"
```

---

### Task 7: 侧栏与小部件

**Files:**
- Create: `Nimbus/partials/sidebar.php`
- Create: `Nimbus/widgets/author.php`, `notice.php`, `hot.php`, `recent-comments.php`, `tags.php`, `categories.php`, `calendar.php`, `site-info.php`, `custom-html.php`
- Create: `Nimbus/core/Widgets.php`
- Create: `Nimbus/assets/css/components/sidebar.css`
- Modify: `Nimbus/index.php`（接入 sidebar）
- Test: `tests/unit/WidgetsTest.php`

**Interfaces:**
- Produces：`Nimbus\Widgets::order(?string $raw): array`（按行拆分、trim、去 `#` 注释、去重、仅保留已知名单 `author notice hot recent-comments tags categories calendar site-info custom-html`；空则默认顺序）；`Widgets::render(string $name, $archive): void`（include `widgets/$name.php`）。`sidebar.php`：`SidebarPosition != none` 时输出 `<aside class="n-side"><div class="n-side-sticky">` 循环 `Widgets::order(Options::get('Widgets'))` `</div></aside>`。每个小部件外层 `<section class="n-widget n-widget-<name> n-reveal">`，标题 `<h4 class="n-widget-title">`。
- `author.php`：顶部色带 + 旋转光环头像（`AuthorAvatar` 回落到 Gravatar of 站长邮箱）、`AuthorName`（回落 `$this->options->title`）、`AuthorBio`、统计（文章数 / 评论数 / 标签数，查询 `Typecho_Db` 计数，带静态缓存）、社交图标（`SocialLinks` 解析 `名称|URL|图标`）。`hot.php`：`Post::hot(HotCount)`。`recent-comments.php`：`Typecho_Widget::widget('Widget_Comments_Recent', 'pageSize=5&ignoreAuthor=true')`。`tags.php`：`Widget_Metas_Tag_Cloud`，`limit=30`。`categories.php`：`Widget_Metas_Category_List` 带文章数。`calendar.php`：当月日历，有文章的日期加链接（查询当月 `created` 范围的文章，按日分组）。`site-info.php`：建站天数（`Options::get('SiteSince')`，加到 Site 分组，格式 `YYYY-MM-DD`）、文章 / 评论 / 分类 / 标签总数、最后更新。`custom-html.php`：`CustomWidgetTitle` + 原样输出 `CustomWidgetHtml`。

- [ ] **Step 1: 写 Widgets::order 测试（失败）**

```php
use Nimbus\Widgets;
test('order default when empty', fn() => assert_eq(['author','notice','hot','recent-comments','tags','categories'], Widgets::order('')));
test('order parses, trims, ignores comments & unknown & dupes', fn() => assert_eq(['hot','author'], Widgets::order(" hot \n# notice\nnope\nauthor\nhot")));
```

- [ ] **Step 2: 实现 Widgets、partials、CSS；index.php 接入**

sidebar.css：`.n-side-sticky{position:sticky;top:88px;display:flex;flex-direction:column;gap:20px}`；作者卡 `::before` 色带 84px、头像 `conic-gradient` 光环旋转 6s、内层反向旋转保持图片正向；社交图标 hover 弹起；热门列表 hover 右移 6px；标签 chip hover 变 accent。

- [ ] **Step 3: 测试、浏览器检查（侧栏吸顶、各小部件渲染、`SidebarPosition=left/none`）**

- [ ] **Step 4: 提交**

```bash
git add -A && git commit -m "feat: 侧栏与九个小部件"
```

---

### Task 8: 文章页（正文、TOC、代码高亮、公式、图表、灯箱、文末区块）

**Files:**
- Create: `Nimbus/post.php`, `Nimbus/partials/post-head.php`, `toc.php`, `copyright.php`, `reward.php`, `share.php`, `adjacent.php`, `related.php`, `post-foot.php`, `expire-notice.php`, `password.php`
- Create: `Nimbus/assets/css/components/toc.css`, `code.css`, `lightbox.css`, `components/post-foot.css`, `pages/post.css`
- Create: `Nimbus/assets/js/modules/toc.js`, `code.js`, `lightbox.js`, `math.js`, `mermaid.js`, `share.js`, `pages/post.js`
- Create: `Nimbus/assets/vendor/prism/prism.js`, `prism.css`（从 prismjs 官网定制包：core + markup/css/clike/javascript/typescript/php/python/java/go/rust/bash/json/yaml/sql/markdown/c/cpp/csharp/kotlin/swift/ruby/docker/nginx/diff + line-numbers + toolbar + copy-to-clipboard 插件，`npx` 不可用则用 `curl https://cdn.jsdelivr.net/npm/prismjs@1.29.0/...` 拼装各组件到本地）、`LICENSE`
- Create: `Nimbus/assets/vendor/katex/`（katex.min.js、katex.min.css、auto-render.min.js、fonts/、LICENSE，0.16.x），`Nimbus/assets/vendor/mermaid/mermaid.min.js`（10.x 或 11.x ESM 版，LICENSE）
- Create: `Nimbus/core/Content.php`
- Test: `tests/unit/ContentTest.php`；smoke 文章页含 `data-page="post"`、`class="n-prose"`

**Interfaces:**
- Produces：`Nimbus\Content::render(string $html, array $opts): string`（后处理正文：① 给 `<table>` 包 `<div class="n-table-wrap">`；② `<img>` 加 `loading="lazy" decoding="async"`，无 alt 时加空 alt，`LightboxEnable` 时包 `<a data-lightbox href=src>`（已在 `<a>` 内的跳过）；③ `<pre><code class="language-x">` 加 `data-lang="x"`，超过 `CodeFoldLines` 行时 `<pre>` 加 `data-fold`；④ `language-mermaid` 的 `<pre><code>` 替换为 `<div class="mermaid">内容</div>`；⑤ 外链 `<a>` 加 `target="_blank" rel="noopener"`；⑥ 返回 `['html'=>…, 'hasMath'=>bool（含 `$$` 或 `\(` 或 `\[`）, 'hasMermaid'=>bool, 'hasCode'=>bool]`）。`Content::renderFrom(...)` 即该函数（纯函数，测试用），`Content::render($archive)` 包装读取 `$archive->content`。
- `post.php`：header（`n-nav-solid`）→ `<div class="n-container"><div class="n-wrap"><article class="n-post n-main" data-cid>` `post-head.php`（分类 chip、标题 `h1`、meta：作者 / 日期 / 字数 / 阅读时长 / 浏览量（Kit）/ 评论数；缩略图作为头部横幅 `.n-post-banner`，无图时不显示） → `expire-notice.php`（`ExpireDays>0 || fields.expireNotice` 且 `now - modified > days`）→ `<div class="n-prose" id="n-prose">` 正文（密码保护时 Typecho 自动输出表单，用 `password.php` 美化：检测 `$this->hidden` 为 true 时输出自定义表单 `<form class="n-password" method="post"><input type="password" name="protectPassword">` + 提示语）`</div>` → `post-foot.php`（标签列表、`copyright.php`、`reward.php`（三张二维码 tab）、`share.php`（微博 / QQ 空间 / 复制链接 / 若 Kit 预留"海报"按钮不显示）、点赞按钮仅 Kit）→ `adjacent.php`（上一篇 / 下一篇卡片，背景缩略图）→ `related.php`（`Post::related`，网格小卡）→ `comments.php`（Task 9）`</article>` `toc.php`（`TocEnable && !hide.toc`：`<aside class="n-toc" data-toc><div class="n-toc-in"><h4>目录</h4><nav id="n-toc-nav"></nav></div></aside>`，桌面端 `.n-wrap` 右列由 TOC 取代侧栏；无 TOC 时显示 sidebar）`</div></div>` → footer。hide 判断读取 fields `nimbusHide` 数组。
- JS：`toc.js`：扫描 `#n-prose h2,h3,h4`，无 id 的生成 `h-<index>`；渲染嵌套 `<ul>`；`IntersectionObserver` 高亮当前；点击平滑滚动；移动端浮动按钮开合。`code.js`：`import(assets+'/vendor/prism/prism.js')`（Prism 配置 `window.Prism = {manual:true}` 先设）后 `Prism.highlightAllUnder(root)`；为每个 `pre[data-lang]` 注入头部（语言名、复制按钮，复制成功文字切 `copied` 1.5s）；`data-fold` 的 pre 加展开按钮；行号用 Prism line-numbers 类（`CodeLineNumbers`）。`lightbox.js`：自写：点击 `[data-lightbox]` 打开全屏 `dialog`，显示图片、关闭按钮、左右切换（同一正文内所有）、`Esc` / 点击遮罩关闭、缩放过渡。`math.js`：`NimbusConfig.math === 'on' || (auto && hasMath)` 时加载 KaTeX css/js + auto-render，`renderMathInElement(prose, {delimiters: [{left:'$$',right:'$$',display:true},{left:'\\(',right:'\\)',display:false},{left:'\\[',right:'\\]',display:true}]})`。`mermaid.js`：同理，`import(mermaid.esm)` 后 `mermaid.initialize({startOnLoad:false, theme: dark皮肤?'dark':'default'})`，`mermaid.run({nodes})`；皮肤切换时重渲染（监听 `nimbus:theme`，theme.js 在切换后派发）。`share.js`：复制链接用 `navigator.clipboard`，失败回退 `execCommand`；微博 / QQ 用标准分享 URL 新窗口。`pages/post.js`：按 `NimbusConfig` 标志初始化上述模块 + reveal。

- [ ] **Step 1: 写 Content 测试（失败）**

```php
use Nimbus\Content;
test('wraps table', fn() => assert_true(strpos(Content::renderFrom('<table><tr><td>1</td></tr></table>', ['lightbox'=>false,'fold'=>30])['html'], '<div class="n-table-wrap"><table') === 0));
test('img lazy + lightbox', function () { $r = Content::renderFrom('<p><img src="a.jpg"></p>', ['lightbox'=>true,'fold'=>30]); assert_true(strpos($r['html'], 'loading="lazy"') !== false); assert_true(strpos($r['html'], '<a data-lightbox href="a.jpg"') !== false); });
test('img inside link not wrapped', fn() => assert_true(strpos(Content::renderFrom('<a href="x"><img src="a.jpg"></a>', ['lightbox'=>true,'fold'=>30])['html'], 'data-lightbox') === false));
test('code lang and fold', function () { $code = implode("\n", array_fill(0, 40, 'x')); $r = Content::renderFrom('<pre><code class="language-php">' . $code . '</code></pre>', ['lightbox'=>false,'fold'=>30]); assert_true(strpos($r['html'], 'data-lang="php"') !== false); assert_true(strpos($r['html'], 'data-fold') !== false); assert_eq(true, $r['hasCode']); });
test('mermaid converted', function () { $r = Content::renderFrom('<pre><code class="language-mermaid">graph TD;A--&gt;B</code></pre>', ['lightbox'=>false,'fold'=>30]); assert_true(strpos($r['html'], '<div class="mermaid">graph TD;A-->B</div>') !== false); assert_eq(true, $r['hasMermaid']); });
test('detects math', fn() => assert_eq(true, Content::renderFrom('<p>$$x^2$$</p>', ['lightbox'=>false,'fold'=>30])['hasMath']));
test('external link target', fn() => assert_true(strpos(Content::renderFrom('<a href="https://x.com/a">x</a>', ['lightbox'=>false,'fold'=>30,'host'=>'me.com'])['html'], 'target="_blank"') !== false));
```

- [ ] **Step 2: 实现 Content（用正则 + `DOMDocument` 皆可；推荐 `DOMDocument` + `libxml_use_internal_errors(true)`，`loadHTML('<?xml encoding="utf-8" ?><div id="root">…</div>')`，输出时取 root 内部 HTML）、vendor 下载、partials、CSS、JS**

code.css：`.n-prose pre{background:var(--code-bg);color:var(--code-fg);border-radius:var(--r);padding:44px 18px 16px;position:relative;font:14px/1.7 var(--font-mono)}`，头部 `.n-code-head`（语言标签 + 复制按钮，顶部 36px 条，左侧三个圆点装饰）；Prism token 配色随皮肤（定义 `--tok-kw --tok-str --tok-num --tok-cm --tok-fn --tok-op` 六个变量进三套皮肤：sky 用 One Dark 色、dusk 用暖色调、cyber 用霓虹青 / 品红）；折叠态 `max-height: 400px` + 底部渐变遮罩 + 展开按钮。

toc.css：桌面 `.n-toc{width:var(--side-w)}.n-toc-in{position:sticky;top:88px}`，列表项左侧 2px 线，当前项 accent 色加粗并平移；移动端 `<1000px` 隐藏，改为右下角浮动按钮弹出抽屉。

- [ ] **Step 3: 测试与浏览器检查**

Run 全部脚本。浏览器打开含代码 / 公式 / mermaid / 图片的测试文章：高亮与复制按钮、折叠、KaTeX 渲染、Mermaid 渲染且切换皮肤后重渲染、灯箱打开关闭、TOC 高亮跟随、打赏 tab、复制链接提示、上下篇与相关文章、密码文章表单样式、三套皮肤截图。

- [ ] **Step 4: 提交**

```bash
git add -A && git commit -m "feat: 文章页正文处理、目录、代码高亮、公式、灯箱与文末区块"
```

---

### Task 9: 评论区

**Files:**
- Create: `Nimbus/comments.php`, `Nimbus/core/Comment.php`, `Nimbus/assets/css/components/comment.css`, `Nimbus/assets/js/modules/comment.js`, `Nimbus/assets/img/emoji.json`
- Modify: `Nimbus/assets/js/pages/post.js`（初始化 comment）
- Test: `tests/unit/CommentTest.php`

**Interfaces:**
- Produces：`Nimbus\Comment::avatar(string $mail, int $size = 80): string`（md5 小写邮箱，`GravatarMirror` + hash + `?s=&d=identicon`）；`Comment::render(\Widget_Comments_Archive $c, array $options): void`（`listComments` 回调函数名 `nimbusCommentRender`，在 `comments.php` 定义并转发到 `Comment::render`）；`Comment::isAuthor($c): bool`（`$c->authorId == $c->ownerId`）；`Comment::emojiReplace(string $text, array $map): string`（把 `:smile:` 替换为 `<img class="n-emoji" src alt>`，供第 2 期；第 1 期 emoji.json 是 Unicode 表情分组 `{"笑脸":["😀","😂",…],"手势":[…],"动物":[…],"食物":[…]}`，面板点击插入字符）。
- `comments.php`：`<section id="comments" class="n-comments">` 标题（`N 条评论`）→ `$comments->listComments(['before'=>'<ol class="n-comment-list">','after'=>'</ol>'])` → 评论分页 `$comments->pageNav('‹','›',1,'…',['wrapTag'=>'nav','wrapClass'=>'n-pagination'])` → 回复表单 `<form id="n-comment-form" method="post" action="<?php $this->commentUrl() ?>">`（`$this->allow('comment')` 时）：昵称 / 邮箱 / 网址三列（登录用户显示"以 X 身份评论"）、textarea、工具条（表情按钮、预览按钮、`$this->remember()` 填充）、`$comments->reply()` 的回复锚点 `#respond` 与"取消回复"。每条评论 `<li id="comment-<coid>" class="n-comment [is-author]">` 头像、昵称（链接）、博主徽标、时间（`data-time` 相对时间由 JS 可选）、内容、回复按钮 `$c->reply('回复')`；子评论 `<ol class="n-comment-children">`。
- `comment.js`：接管 Typecho 的 `TypechoComment.reply`（移动表单到评论下方，`cancel` 还原）；表情面板（从 `emoji.json` fetch，分组 tab，点击插入到光标）；预览（简单 Markdown：段落、`**`、`*`、`` ` ``、链接）；若 `NimbusConfig.api` 非 null 预留 AJAX 提交分支（第 2 期）；提交按钮 loading 态。

- [ ] **Step 1: 测试（失败）**

```php
use Nimbus\Comment;
test('avatar url', fn() => assert_eq('https://cravatar.cn/avatar/' . md5('a@b.com') . '?s=80&d=identicon', Comment::avatarUrl('A@B.com ', 80, 'https://cravatar.cn/avatar/')));
test('emoji replace', fn() => assert_eq('hi <img class="n-emoji" src="/e/smile.png" alt="smile">', Comment::emojiReplace('hi :smile:', ['smile'=>'/e/smile.png'])));
```

- [ ] **Step 2: 实现**

comment.css：评论卡片无边框、头像 44px 圆、嵌套缩进 52px（移动端 20px）并左侧细线；博主徽标 accent 小标签；表单输入框 `background:var(--chip);border:1px solid transparent;focus 时 border accent + 光晕`；表情面板弹层 `.n-emoji-panel`。

- [ ] **Step 3: 测试、浏览器：发表评论（本地站）、回复移动表单、取消、表情插入、预览**

- [ ] **Step 4: 提交**

```bash
git add -A && git commit -m "feat: 评论区渲染、表单、表情与预览"
```

---

### Task 10: 独立页模板（page / archive / archives / links / about / 404）

**Files:**
- Create: `Nimbus/page.php`, `archive.php`, `404.php`, `page-archives.php`, `page-links.php`, `page-about.php`
- Create: `Nimbus/assets/css/pages/archive.css`, `links.css`, `error.css`, `page.css`
- Create: `Nimbus/assets/js/pages/page.js`, `archive.js`, `error.js`
- Create: `Nimbus/core/Links.php`
- Create: `Nimbus/assets/img/404.svg`
- Test: `tests/unit/LinksTest.php`；smoke 对 `/index.php/archives.html`、`/index.php/links.html`、404 的断言

**Interfaces:**
- `page.php`：与 post.php 相同骨架但无 adjacent/related/copyright/reward，`fields.nimbusHide` 同样生效；评论可用。
- `archive.php`：Typecho 用于分类 / 标签 / 搜索 / 日期归档列表 → 直接 `include 'index.php'` 逻辑（`$this->need('index.php')` 不可用，改为 `require __DIR__ . '/index.php'`）。
- `page-archives.php`（模板名 `归档`）：查询全部已发布文章（`Typecho_Widget::widget('Widget_Contents_Post_Recent', 'pageSize=10000')`），按年 → 月分组输出时间线 `<div class="n-timeline"><section class="n-year" data-year><h2 class="n-year-title">2026</h2><div class="n-month"><h3>10 月</h3><ul><li><time>02</time><a>标题</a></li>`；年份标题 sticky。
- `page-links.php`（`友链`）：`Nimbus\Links::all($page): array`（优先 `Kit::links()`；否则解析 `fields.links` JSON；都空返回 `[]`）；`Links::parse(?string $json): array`（纯函数，校验每项 `name,url` 必填，`avatar,desc` 可空，非法项丢弃）。渲染卡片网格 `.n-links`，头像 `onerror` 换首字母占位（`data-letter`），下方输出页面正文（申请说明）与评论。
- `page-about.php`（`关于`）：大头图（作者头像）+ 正文 + 社交链接 + 评论。
- `404.php`：插画 `404.svg`、"页面不见了"、搜索框、随机 5 篇文章（`Typecho_Db` `ORDER BY RAND()` 在 SQLite 为 `RANDOM()`：用 `Typecho_Db::get()->getAdapterName()` 判断，或取最新 20 篇在 PHP 端 `shuffle`，采用后者）、返回首页按钮。HTTP 状态由 Typecho 设置。

- [ ] **Step 1: Links::parse 测试（失败）**

```php
use Nimbus\Links;
test('parse valid', fn() => assert_eq([['name'=>'A','url'=>'https://a','avatar'=>'','desc'=>'']], Links::parse('[{"name":"A","url":"https://a"}]')));
test('parse drops invalid', fn() => assert_eq([], Links::parse('[{"name":"A"},{"url":"x"}]')));
test('parse null on bad json', fn() => assert_eq([], Links::parse('{')));
```

- [ ] **Step 2: 实现模板、CSS、JS**

- [ ] **Step 3: smoke（增加 links.html 含 `class="n-links"`、archives 含 `n-timeline`、404 含 `n-error`）、浏览器检查**

- [ ] **Step 4: 提交**

```bash
git add -A && git commit -m "feat: 独立页、归档、友链、关于与 404 模板"
```

---

### Task 11: 无刷新页面切换（router）

**Files:**
- Create: `Nimbus/assets/js/modules/router.js`, `Nimbus/assets/js/modules/router-rules.js`
- Modify: `Nimbus/assets/js/app.js`（`RouterEnable` 时初始化 router，并在 `nimbus:page` 时重新 boot；所有页面模块实现 `destroy`）
- Test: `tests/js/router-rules.test.mjs`（Node 原生 `node --test`）

**Interfaces:**
- `router-rules.js` 导出 `shouldIntercept(anchor: {href, target, download, hash, hasAttribute(name)}, location: {origin, pathname, hash}, adminPath: string): boolean`：外域 → false；`target` 非空且非 `_self` → false；有 `download` → false；`data-no-router` → false；`href` 以 `#` 开头或仅 hash 不同 → false；路径以 `adminPath` 开头 → false；扩展名 ∈ `pdf zip rar 7z jpg jpeg png gif webp svg mp3 mp4 xml txt` → false；`mailto:`/`tel:`/`javascript:` → false；否则 true。
- `router.js`：`init()`：委托 `click`，`shouldIntercept` 为 true 时 `preventDefault`，`navigate(url, push=true)`：显示顶部进度线动画，`fetch(url, {headers:{'X-Nimbus-Router':'1'}})`；响应非 OK 或 `content-type` 非 `text/html` → `location.href = url`；解析 `DOMParser`，取 `#n-main` 与 `title`、`body` 的 `data-page/data-cid/class`，`document.startViewTransition` 可用时包裹替换；替换后执行新页面的 `<main>` 内 `<script>`（重建 script 节点）；更新 `NimbusConfig`（新页面 `<script id="n-config" type="application/json">` 读取——header.php 需把 NimbusConfig 以 JSON script 标签输出，app.js 启动时解析；本任务修改 header.php 输出方式）；`history.pushState`；滚到顶（或 hash 元素）；派发 `nimbus:page`。`popstate` 时 `navigate(location.href, false)`。页面模块在 `nimbus:page` 前调用 `destroy()`（app.js 维护当前模块实例）。

- [ ] **Step 1: Node 测试（失败）**

```js
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { shouldIntercept } from '../../Nimbus/assets/js/modules/router-rules.js';
const loc = { origin: 'http://me.com', pathname: '/', hash: '' };
const a = (href, extra = {}) => ({ href, target: '', download: null, hash: '', hasAttribute: n => !!extra[n], ...extra });
test('same origin page', () => assert.equal(shouldIntercept(a('http://me.com/archives/1/'), loc, '/admin/'), true));
test('external', () => assert.equal(shouldIntercept(a('http://x.com/'), loc, '/admin/'), false));
test('blank target', () => assert.equal(shouldIntercept(a('http://me.com/a', { target: '_blank' }), loc, '/admin/'), false));
test('download', () => assert.equal(shouldIntercept(a('http://me.com/a.zip'), loc, '/admin/'), false));
test('admin', () => assert.equal(shouldIntercept(a('http://me.com/admin/'), loc, '/admin/'), false));
test('hash only', () => assert.equal(shouldIntercept(a('http://me.com/#top', { hash: '#top' }), loc, '/admin/'), false));
test('data-no-router', () => assert.equal(shouldIntercept(a('http://me.com/a', { 'data-no-router': true }), loc, '/admin/'), false));
```

Run: `node --test tests/js/` → FAIL。

- [ ] **Step 2: 实现 rules 与 router；改 header.php 输出 `<script id="n-config" type="application/json">`；app.js 解析并支持重 boot**

- [ ] **Step 3: `node --test tests/js/` 通过；浏览器：首页 → 文章 → 分类 → 返回，皆无整页刷新（Network 面板 fetch），过渡动画存在，评论表单 / 代码高亮 / TOC 在切换后仍工作；外链新开；关闭 `RouterEnable` 后恢复整页跳转**

- [ ] **Step 4: 提交**

```bash
git add -A && git commit -m "feat: 基于 fetch 与 View Transitions 的无刷新页面切换"
```

---

### Task 12: 后台配置页体验与前台皮肤 / 外观选项落地

**Files:**
- Modify: `Nimbus/assets/css/admin.css`, `Nimbus/assets/js/admin.js`
- Modify: `Nimbus/partials/header.php`（落地 `Radius`、`Motion`、`Mourning`、`FontScheme`、`ThemeSwitchable`、`Debug`）
- Modify: `Nimbus/core/Config/Appearance.php`（补充说明文案与预览色块 HTML 在 description 中）

**Interfaces:**
- `header.php` 在 `<html>` 上输出 `data-theme`（内联脚本）、`data-radius`、`data-motion`、`data-mourning`（仅开启时）、`data-font`；`ThemeSwitchable=0` 时不渲染主题按钮；`Debug=1` 时在 `NimbusConfig.debug=true` 并由 app.js `console.table` 配置。
- admin.js 增强：图片类字段（name 含 `Image|Avatar|Favicon|Reward|HeroImages|ThumbPool`）在输入框下方显示预览缩略图；`Widgets` 字段旁显示可用小部件名清单；皮肤单选项旁显示三色色板预览（description 内 HTML）；"保存"后保持当前 tab。

- [ ] **Step 1: 实现并在本地站后台检查（登录 admin/admin123 → 外观 → 设置外观）：Tab 切换记忆、预览、保存成功**

- [ ] **Step 2: 前台验证 `Radius=lg`、`Motion=off`、`Mourning=1`、`ThemeSwitchable=0` 各生效**

- [ ] **Step 3: 提交**

```bash
git add -A && git commit -m "feat: 后台配置页交互与外观选项落地"
```

---

### Task 13: 字体、图片资源、SEO 校验、README 与发布准备

**Files:**
- Create: `Nimbus/assets/fonts/NotoSerifSC-subset.woff2`（用 `pyftsubset` 若可用，否则下载 Google Fonts 的 `Noto Serif SC` 中文常用 3500 字子集 woff2；不可得时改用 `FontScheme=serif` 加载本地回退并在 README 说明）、`JetBrainsMono-subset.woff2`、`fonts.css`（`@font-face`，`font-display: swap`）
- Create: `Nimbus/screenshot.png`（用内置浏览器对首页 1280×800 截图三套皮肤拼成一张或取 sky）
- Create: `Nimbus/README.md`（安装、配置说明、三套皮肤截图、小部件名清单、自定义菜单 JSON 格式、友链 JSON 格式、NimbusKit 说明、FAQ、许可）、`Nimbus/CHANGELOG.md`、根 `README.md`（指向两目录）、`docs/user-guide.md`
- Modify: `Nimbus/core/Seo.php` 校验输出（用 `tests/smoke.sh` 增加 `og:title`、`application/ld+json` 断言）

- [ ] **Step 1: smoke 增加 SEO 断言（失败）→ 完善 Seo → 通过**

- [ ] **Step 2: 字体与截图落地，README 编写**

- [ ] **Step 3: 全量验证：`bash tests/lint.sh && php tests/run.php && node --test tests/js/ && bash tests/css-vars.sh && bash tests/smoke.sh`；内置浏览器对三套皮肤 × 首页 / 文章 / 归档 / 友链 / 404 共 15 个页面截图检查，控制台零错误；移动端 375 宽检查首页与文章页**

- [ ] **Step 4: 提交并打 tag**

```bash
git add -A && git commit -m "chore: 字体资源、README、截图与 1.0.0 发布准备" && git tag v1.0.0-rc1
```
