# Nimbus 主题 · 第 1 期设计文档

日期：2026-10-09
状态：已批准（视觉方向 v4 与技术方案一由项目所有者确认；其余细节授权设计者自行决定）

## 1. 目标与范围

Nimbus 是一个开源免费（MIT）的 Typecho 主题，视觉参考 Cuteen 5.x，但代码完全重写，不复用 Cuteen 的任何源码、混淆代码或闭源插件。

目标用户：中文个人博客作者。成功标准：

- 上传主题目录即可使用，不装插件也功能完整；装上 NimbusKit 后解锁互动功能。
- 三套皮肤（晴空 / 暮色 / 赛博朋克）一键切换，视觉与动效达到 v4 效果图水准。
- 零构建：仓库里的文件就是运行文件，用户可直接修改。
- PHP 7.4 – 8.4，Typecho 1.2.1+。

第 1 期范围（本文档）：主题本体。第 2 期 NimbusKit 插件、第 3 期增强功能各自另写 spec。

### 1.1 第 1 期功能清单

首页与列表

- Hero 区：背景图（固定 / 随机图列表 / 每日必应可选）、Ken Burns 缓动 + 视差、两团漂浮光斑、标题后半段流光、打字机副标题（多句轮播）、状态胶囊、底部波浪过渡。可整体关闭。
- 置顶轮播：按配置的 cid 列表取文章，自动切换带进度指示点，无置顶时不渲染。
- 三种列表样式：图文交错卡片（默认）、三栏网格、纯文字列表；后台单选。
- 分页：传统分页 / 无限滚动 / 加载更多，三选一。
- 卡片：缩略图提取顺序为自定义字段 thumb → 正文首图 → 分类默认图 → 随机图池；悬停动效按 v4。

文章页

- 顶部阅读进度线、右下返回顶部进度环。
- 文章头：标题、分类、日期、字数、预计阅读时长、浏览量（有 Kit 时）。
- 正文排版：中文优化的 typography，图片懒加载 + 灯箱，表格横向滚动，引用、脚注样式。
- 代码块：Prism 高亮，行号、语言标签、一键复制、超过 N 行可折叠、三套皮肤各自配色。
- 数学公式：检测 `$$` / `\(` 后懒加载 KaTeX。Mermaid：检测 `language-mermaid` 后懒加载。
- 浮动目录（TOC）：桌面端右侧吸顶，滚动高亮当前节；移动端折叠按钮。
- 文末：版权声明卡、打赏（支付宝 / 微信 / QQ 二维码，可关）、点赞按钮（有 Kit 时）、分享（微博 / QQ / 复制链接）、标签、上一篇 / 下一篇卡片、相关文章（同标签优先，再同分类）。
- 文章过期提醒（超过 N 天显示提示条）、密码保护页美化。

评论

- 使用 Typecho 原生评论流程（无 Kit 时整页提交；有 Kit 时 AJAX）。
- 楼中楼、Gravatar 镜像可配、博主标识、表情面板（内置一套 emoji 文本表情，图片表情在第 2 期）、评论预览、评论分页。

页面模板

- 归档页（按年月分组时间线）、友链页（读取自定义字段 JSON 或 Kit 数据）、关于页、404 页（含搜索框与随机文章）。

侧栏小部件（后台可排序、可开关）

- 作者卡、公告、热门文章（按评论数；有 Kit 时按浏览量）、最新评论、标签云、分类、日历、站点信息、自定义 HTML。

全局

- 三套皮肤 + 跟随系统；首屏无闪烁。
- 浮动胶囊导航（滚过 Hero 后出现）、多级下拉菜单、移动端抽屉、搜索弹窗（无 Kit 时跳转原生搜索；有 Kit 时即时搜索）。
- 无刷新页面切换（fetch + View Transitions，自动降级）。
- 哀悼模式（全站灰度）、ICP / 公安备案、自定义头尾代码、自定义 CSS / JS、静态资源 CDN 前缀、字体选择（系统 / 内置思源宋体子集 / 自定义 URL）。
- SEO：OpenGraph、Twitter Card、JSON-LD（Article / WebSite）、canonical。
- 后台配置页：分组 Tab、颜色与图片预览、说明文案，基于 Typecho 原生表单。

## 2. 架构

### 2.1 目录结构

```
Nimbus/                         仓库根
├── Nimbus/                     主题目录（复制到 usr/themes/Nimbus）
│   ├── index.php               首页 / 分类 / 标签 / 搜索列表
│   ├── post.php  page.php  archive.php  404.php  comments.php
│   ├── page-archives.php  page-links.php  page-about.php
│   ├── functions.php           themeConfig / themeFields / themeInit 入口，require core/Bootstrap.php
│   ├── core/
│   │   ├── Bootstrap.php       常量、自动加载（spl_autoload_register，命名空间 Nimbus\）、版本检查
│   │   ├── Options.php         配置读取、默认值、类型转换；模板只通过它取配置
│   │   ├── Config/             后台配置项，每个分组一个类，返回 Typecho 表单元素数组
│   │   │   ├── Site.php  Index.php  Post.php  Sidebar.php  Appearance.php  Advanced.php
│   │   ├── Admin.php           后台配置页渲染（Tab、说明、admin.css/js 注入）
│   │   ├── View.php            partial()、e()（转义）、asset()（带版本号）、icon()（内联 SVG）
│   │   ├── Post.php            thumb()、excerpt()、readingTime()、wordCount()、adjacent()、related()
│   │   ├── Menu.php            导航菜单树（Typecho 页面 + 自定义菜单 JSON）
│   │   ├── Comment.php         评论列表渲染回调、头像 URL、楼层、博主判断
│   │   ├── Seo.php             meta / OpenGraph / JSON-LD 输出
│   │   └── Kit.php             NimbusKit 适配层：enabled()、views()、likes()、apiUrl()；未启用时返回 null/0
│   ├── partials/               header.php footer.php nav.php hero.php top-carousel.php
│   │                           post-card.php post-grid.php post-plain.php pagination.php
│   │                           sidebar.php toc.php copyright.php reward.php share.php
│   │                           adjacent.php related.php search-modal.php
│   ├── widgets/                author.php notice.php hot.php recent-comments.php tags.php
│   │                           categories.php calendar.php site-info.php custom-html.php
│   ├── assets/
│   │   ├── css/
│   │   │   ├── tokens.css      公共令牌：间距、圆角、字号、阴影、缓动、z-index
│   │   │   ├── theme-sky.css  theme-dusk.css  theme-cyber.css   皮肤变量（颜色、渐变、光晕、皮肤特效）
│   │   │   ├── base.css        reset、排版基础、通用工具类（少量）
│   │   │   ├── layout.css      栅格、容器、响应式断点
│   │   │   ├── components/     nav.css hero.css card.css sidebar.css comment.css toc.css code.css modal.css button.css
│   │   │   ├── pages/          post.css archive.css links.css error.css
│   │   │   ├── typography.css  正文排版
│   │   │   ├── nimbus.css      @import 以上全部，模板只引这一个（HTTP/2 下多文件可接受；后续可提供合并脚本）
│   │   │   └── admin.css
│   │   ├── js/
│   │   │   ├── app.js          入口：读取 data-page，初始化全局模块，按需 import 页面模块
│   │   │   ├── modules/        theme.js router.js nav.js hero.js carousel.js reveal.js
│   │   │   │                   progress.js lightbox.js toc.js code.js search.js comment.js
│   │   │   │                   infinite.js math.js mermaid.js share.js
│   │   │   └── admin.js
│   │   ├── vendor/             prism/ katex/ mermaid/（本地化，带 LICENSE）
│   │   ├── fonts/              思源宋体 / Inter 子集 woff2
│   │   └── img/                默认缩略图池、头像、404 插画、emoji.json
│   ├── screenshot.png  README.md  LICENSE  CHANGELOG.md
├── NimbusKit/                  第 2 期
├── docs/                       设计文档、用户手册、贡献指南
├── tests/                      见第 6 节
├── .dev/                       本地测试站（gitignore）
└── .gitignore  README.md  LICENSE
```

### 2.2 PHP 层约定

- `functions.php` 只做三件事：`require core/Bootstrap.php`；`themeConfig($form)` 委托给 `Nimbus\Admin::render($form)`；`themeFields($layout)` 委托给 `Nimbus\Config\Fields::render($layout)`；`themeInit($archive)` 委托给 `Nimbus\Bootstrap::init($archive)`。
- `Bootstrap::init()`：设置评论默认项（允许回复、嵌套层数）、注册输出缓冲处理（仅在需要时，如哀悼模式不需要）、检测 Kit。
- `Options::get(string $key, $default = null)`：读取 `Helper::options()->{$key}`，空值回落到 `Config/*` 声明的默认值；`Options::bool()` / `int()` / `list()`（逗号 / 换行分隔）做类型转换。
- `View::partial(string $name, array $vars = [])`：`extract` 变量后 include `partials/{$name}.php`；所有输出走 `View::e()` 转义，除非明确是 HTML（正文、自定义代码）。
- 命名空间 `Nimbus\`，PSR-4 映射到 `core/`，自动加载用 `spl_autoload_register`，不依赖 Composer。
- 所有类方法静态或使用 `Typecho_Widget` 实例作参数，不保存全局状态（Kit 检测结果除外，缓存在静态属性）。
- 兼容 Typecho 1.2.x 新旧类名：使用 `Typecho_Widget_Helper_Form_Element_*`（1.2 仍保留别名），`Helper::options()`。

### 2.3 CSS 架构

- 设计令牌两层：`tokens.css` 为皮肤无关令牌；`theme-*.css` 为皮肤令牌，挂在 `:root[data-theme="sky"]` 等选择器上。组件只引用变量，不写具体颜色。
- 皮肤变量清单（必须三套都定义）：`--bg --fg --fg-strong --fg-soft --muted --card --line --line-hover --chip --glass --accent --accent-text --accent-soft --on-accent --accent-grad --accent-glow --orb-a --orb-b --title-grad --shadow --shadow-hover --hero-overlay --code-bg --code-fg --selection`。赛博朋克额外：`--scanline`、`--grid`、`--font-display`。
- 使用原生 CSS 嵌套、`color-mix()`、容器查询、`@layer`（reset / base / components / pages / utilities）控制优先级。
- `prefers-reduced-motion: reduce` 时关闭所有动画与过渡。
- 断点：`< 640` 手机，`640–1000` 平板（单栏），`> 1000` 桌面双栏；侧栏 320px，内容最大宽 1180px。
- 字体：标题 `--font-title`（默认思源宋体子集 + 系统衬线回退），正文系统无衬线栈，代码 JetBrains Mono 子集回退等宽；三者可在后台改。

### 2.4 JS 架构

- 全部 ES Modules，`<script type="module" src="app.js">`；不支持 module 的浏览器得到无 JS 的可用页面。
- `app.js`：读取 `document.body.dataset.page`（index / post / page / archive / error），初始化全局模块 `theme nav progress search reveal router`，再 `import()` 页面模块。每个模块导出 `init(root)` 与 `destroy()`，router 切页后对新内容重新 `init`。
- `theme.js`：首屏前一段内联脚本（放在 `<head>`）读取 `localStorage.nimbus-theme` 或系统偏好并设 `data-theme`；模块负责切换按钮、View Transition 圆形扩散过渡、同步 `<meta name="theme-color">`。
- `router.js`：代理点击站内 `<a>`（排除 `target`、下载、锚点、后台、带 `data-no-router`），`fetch` HTML，解析出 `<main>` 与 `<title>`，用 `document.startViewTransition` 替换，`history.pushState`，滚动复位，派发 `nimbus:page` 事件；失败时回退 `location.href`。
- 第三方库通过 `import()` 从 `assets/vendor` 懒加载，路径以 `NimbusConfig.assets` 为前缀（CDN 可替换）。
- `window.NimbusConfig` 由 `partials/header.php` 以 JSON 输出：assets、api（Kit URL 或 null）、page、cid、theme 设置、文案。

### 2.5 皮肤切换与无闪烁

`<head>` 内联：

```html
<script>(function(){var t=localStorage.getItem('nimbus-theme');if(!t||t==='auto'){t=matchMedia('(prefers-color-scheme: dark)').matches?'<默认暗色皮肤>':'<默认亮色皮肤>'}document.documentElement.dataset.theme=t})()</script>
```

后台可设置：默认亮色皮肤（sky）、默认暗色皮肤（dusk / cyber）、是否允许访客切换、是否跟随系统。

## 3. 数据与配置

### 3.1 后台配置分组（Config/*）

Site（站点）：Logo 图 / 文字、Favicon、副标题、备案 ICP、公安备案、页脚自定义 HTML、作者头像、作者签名、社交链接（JSON 列表：名称、URL、图标名）。

Index（首页）：Hero 开关、Hero 图片列表（每行一个，多个则随机）、Hero 高度、主标题、副标题句子列表、状态胶囊文字、置顶文章 cid 列表、列表样式（card / grid / plain）、分页模式、每页摘要字数、默认缩略图池。

Post（文章）：显示字数 / 阅读时长、TOC 开关、代码行号 / 折叠阈值、版权声明开关与文案、打赏开关与三张二维码 URL、分享开关、相关文章数量、过期提醒天数、图片灯箱开关、数学公式 / Mermaid 开关（默认自动检测）。

Sidebar（侧栏）：侧栏位置（左 / 右 / 无）、小部件顺序与开关（textarea，每行一个 widget 名，支持 `#` 注释）、公告内容、热门文章数量、自定义 HTML。

Appearance（外观）：默认亮色皮肤、默认暗色皮肤、跟随系统、允许访客切换、字体方案、字体 CDN / 自定义字体 URL、圆角档位（小 / 中 / 大）、动效强度（关 / 克制 / 全部）、哀悼模式。

Advanced（高级）：静态资源 CDN 前缀、自定义头部代码、自定义底部代码、自定义 CSS、自定义 JS、无刷新跳转开关、自定义菜单 JSON、Gravatar 镜像、调试模式（输出 Options 快照到控制台）。

每个配置项声明：`name`、`type`、`label`、`description`、`default`、`options`（单选 / 多选）。`Admin::render()` 按分组渲染为 Tab，Tab 状态存 `localStorage`。

### 3.2 文章自定义字段（themeFields）

- `thumb`：缩略图 URL。
- `summary`：自定义摘要。
- `hideTitle`、`hideToc`、`hideCopyright`、`hideReward`：布尔。
- `password_hint`：密码保护页提示语。
- `links`（仅友链页模板）：JSON 数组 `[{name,url,avatar,desc}]`。
- `expireNotice`：覆盖全局过期天数（0 关闭）。

### 3.3 Kit 适配接口

`Nimbus\Kit` 暴露：

```php
Kit::enabled(): bool                // 插件已激活且版本 >= 要求
Kit::apiUrl(): ?string              // 如 /nimbus-api
Kit::views(int $cid): ?int          // 浏览量，未启用 null
Kit::likes(int $cid): ?int
Kit::hotPosts(int $limit): ?array   // 按浏览量；未启用 null，调用方回落到评论数排序
Kit::links(): ?array                // 友链数据；未启用回落到自定义字段
```

前端通过 `NimbusConfig.api` 是否为 null 决定是否显示点赞按钮、即时搜索、AJAX 评论。插件未启用时后台配置页顶部显示一条友好提示（含插件下载链接），前台不输出任何错误。

### 3.4 数据流

- 请求 → Typecho 路由 → 模板文件 → `View::partial()` 组装 → 输出。模板中取数据：`$this`（Widget）给内容，`Options` 给配置，`Post` 给派生数据，`Kit` 给插件数据。
- 热门文章 / 相关文章 / 上下篇：通过 `Typecho_Db` 查询封装在 `Post` 中，带 `static` 内存缓存，单次请求内不重复查询。
- 随机图 / Hero 图：`Options::list()` 后在 PHP 端按 `cid` 取模选择，保证同一篇文章缩略图稳定。

## 4. 页面设计要点

- 首页：`partials/hero.php` → 可选 `top-carousel.php` → 列表（按样式选 partial）→ `pagination.php`；侧栏 `sidebar.php`。
- 文章页：`<article data-page="post" data-cid>` 内含头部、正文 `.prose`、尾部区块；TOC 由 JS 扫描 `h2–h4` 生成，不在 PHP 端解析。
- 评论：`comments.php` 用 `$comments->listComments()` 配 `Comment::render` 回调；表单字段顺序昵称 / 邮箱 / 网址 / 内容，带表情按钮与预览。
- 归档页：按年 → 月分组的时间线，左侧竖线与节点，年份吸顶。
- 友链页：卡片网格，头像失败时显示首字母占位。
- 404：插画 + 搜索框 + 随机 5 篇文章。
- 搜索弹窗：`Cmd/Ctrl+K` 或点击打开，无 Kit 时回车跳转 `/search/关键词`。

## 5. 错误处理与降级

- PHP 版本不满足：`functions.php` 顶部检查并 `exit` 友好提示（唯一允许 exit 的地方）。
- 配置缺失：`Options` 默认值兜底，不出现 Notice；`functions.php` 不关闭 `error_reporting`。
- 外部资源（字体 CDN、随机图）失败：CSS 回退字体栈、`onerror` 换占位图。
- JS 模块加载失败：捕获并 `console.warn`，页面保持可用；router 失败退回整页跳转。
- 旧浏览器：无 View Transitions 时直接替换 DOM；无容器查询时用媒体查询回退（关键布局用媒体查询写，容器查询只做增强）。
- Kit 缺失：第 3.3 节。

## 6. 测试策略

- 静态检查：`php -l` 全部 PHP 文件；PHP 7.4 与 8.x 两个版本的语法兼容（本机 8.5，7.4 用 `php -l` 的替代：禁止使用 8.0+ 语法，由代码审查与 `tests/lint-php74.sh` 的正则检查关键特性如 `match`、`enum`、命名参数、`readonly`、联合类型）。
- 单元测试：`tests/unit/` 用一个极简自写断言跑 `Options`、`Post::readingTime/wordCount/excerpt`、`Menu` 树构建、`Kit` 降级逻辑，不依赖 PHPUnit（零依赖原则）。运行 `php tests/run.php`。
- 渲染冒烟测试：`.dev/typecho` 用 SQLite 安装一份 Typecho 1.2.1，脚本 `tests/smoke.sh` 启动 `php -S`，curl 首页 / 文章 / 分类 / 归档 / 404 / 搜索页，断言 HTTP 200、无 PHP Warning、关键标记存在（如 `data-page`、`data-theme`）。
- 浏览器验证：用内置浏览器对三套皮肤截图，检查控制台无错误、Lighthouse 性能 ≥ 90（本地）。
- 每个实施任务完成后运行：`php -l` + 单测 + 冒烟。

## 7. 非目标（第 1 期不做）

点赞 / 浏览量 / AJAX 评论 / 说说 / 相册 / 即时搜索 / 邮件通知 / sitemap（第 2 期）；短代码、分享海报、音乐播放器、统计看板、时间线页、多语言、配置导入导出（第 3 期）。

## 8. 许可与署名

MIT。README 注明"设计灵感来自 Cuteen，代码独立实现"。第三方库保留各自许可证文件于 `assets/vendor/*/LICENSE`。
