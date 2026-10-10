# NimbusKit 第 2 期 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:executing-plans. Steps use checkbox (`- [ ]`) syntax.

**Goal:** 交付 NimbusKit 1.0 插件（浏览量、点赞、即时搜索、AJAX 评论、邮件通知、说说、友链、相册、sitemap）并完成主题侧接入。

**Architecture:** Typecho 1.2 命名空间插件 `TypechoPlugin\NimbusKit`。纯逻辑放 `lib/`（无 Typecho 依赖，单测覆盖）；数据访问集中在 `Store`；对主题只暴露 `NimbusKit_Api` 静态门面；前台接口一个路由分发，后台写操作一个 Action。

**Tech Stack:** PHP 7.4+、Typecho 1.2.1 插件 API（Helper::addRoute / addAction / addPanel、Plugin::factory 钩子）、原生 JS。

**Spec:** `docs/superpowers/specs/2026-10-11-nimbuskit-phase2-design.md`

## Global Constraints

- PHP 7.4 语法；`tests/lint.sh` 覆盖 `NimbusKit/`。
- 插件目录名固定 `NimbusKit`，命名空间 `TypechoPlugin\NimbusKit`，表名前缀 `nimbus_`。
- 接口返回 `{"ok":bool,"data"|"error"}`；写接口校验同源。
- 禁用插件默认不删数据。
- 主题与插件互不强依赖；主题所有插件调用经 `Nimbus\Kit`，插件缺失时返回 null。
- 提交信息中文，结尾 `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`。

## Review Focus

1. 同一访客重复点赞 / 刷新浏览 → 计数不变（Store 唯一索引 + cookie）。
2. 跨站 POST 到 like / view → 403。
3. 说说内容含 `<script>`、`javascript:` 链接 → 输出为纯文本，链接被丢弃。
4. SMTP 不可达 / 认证失败 → 评论照常成功，只记日志；测试邮件给出明确错误。
5. AJAX 评论被 Typecho 拒绝（缺邮箱、频率限制）→ 前端显示原因、保留输入。

---

### Task K1: 插件骨架、建表、统计接口（view / like）

**Files:** `NimbusKit/Plugin.php`、`Api.php`、`Action.php`、`Store.php`、`Schema.php`、`lib/Visitor.php`、`LICENSE`、`README.md`；`tests/unit/KitSchemaTest.php`、`tests/unit/KitVisitorTest.php`；修改 `tests/lint.sh`、`tests/bootstrap.php`（加载 `NimbusKit/lib` 与 `Schema.php`）、`tests/dev-site.sh`（链接并启用插件）。

**Interfaces:**
- `Schema::statements(string $adapter, string $prefix): array` — `$adapter` 取 `mysql|sqlite|pgsql`（由 Typecho 适配器名归一）；返回建表语句数组。`Schema::dropStatements(...)`。`Schema::normalizeAdapter(string $typechoAdapter): string`。
- `Visitor::fingerprint(string $salt, string $ip, string $ua): string`（40 位 sha1）；`Visitor::viewCookie(int $cid): string`；`Visitor::VIEW_TTL = 21600`。
- `Store::views(string $type, int $cid): int`、`likes(...)`、`incrementView(...)`、`like(string $type, int $cid, string $visitor): array{likes:int, liked:bool}`、`hasLiked(...)`、`top(string $field, int $limit): array`。
- `Api::VERSION`、`Api::views(int)`、`Api::likes(int)`、`Api::hotPosts(int): array<int cid>`、`Api::features(): array`。
- 路由 `nimbus_api` → `/nimbus-api/[endpoint]` → `TypechoPlugin\NimbusKit\Action::dispatch`。

**Tests:** Schema 对三种适配器都生成 5 张表、表名带前缀、sqlite 不含 `AUTO_INCREMENT`；Visitor 指纹稳定、不同盐不同、长度 40；冒烟：like 两次计数只加一、跨域 Origin 返回 403、view 带 cookie 不重复计数。

### Task K2: 即时搜索、热门文章、主题点赞与浏览接入

**Files:** `Action.php`（search）、`Store.php`（search）；主题 `core/Kit.php`（features / moments / albums）、`assets/js/modules/like.js`、`assets/js/pages/post.js`（浏览上报 + 点赞）、`partials/post-foot.php`、`assets/css/components/post-foot.css`。

**Tests:** search 结果只含已发布无密码文章、`q` 过短返回错误；浏览器：点赞按钮计数 +1 且刷新后保持已赞态、搜索弹窗即时结果可键盘选择。

### Task K3: AJAX 评论

**Files:** `Hooks.php`（finishComment）、`Plugin.php` 注册钩子；主题 `assets/js/modules/comment.js`。

**Tests:** 冒烟：带 `X-Requested-With` 提交成功返回 JSON；缺邮箱返回非 200 HTML；浏览器：提交后评论区局部刷新并高亮。

### Task K4: 邮件通知

**Files:** `lib/Smtp.php`、`lib/Mailer.php`、`Hooks.php`（通知）、`AdminAction.php`（test-mail）、`Plugin.php` config 邮件项；`tests/unit/KitSmtpTest.php`、`tests/unit/KitMailerTest.php`、`tests/fake-smtp.py`。

**Interfaces:** `Smtp::__construct(array $cfg)`（host、port、secure、user、pass、timeout）、`Smtp::send(string $from, string $fromName, string $to, string $subject, string $html, string $text): void`（失败抛 `RuntimeException`）、`Smtp::buildMessage(...)`（静态，可单测）。`Mailer::recipients(array $comment, ?array $parent, array $cfg): array`、`Mailer::render(string $kind, array $vars): array{subject,html,text}`。

**Tests:** 报文含 `Content-Type: multipart/alternative`、主题 RFC 2047 编码、行以 CRLF 结尾、点开头行加点；收件人规则四种情形；假 SMTP 收到评论通知与测试邮件。

### Task K5: 后台面板与写操作

**Files:** `panel.php`、`AdminAction.php`、`assets/admin.css`、`assets/admin.js`、`lib/Text.php`；`tests/unit/KitTextTest.php`。

**Interfaces:** `Text::moment(string $raw): string`（转义 + 换行转 `<br>` + 自动链接 http(s)）、`Text::photos(string $raw): array`（每行 `URL|说明`）、`Text::safeUrl(string $u): string`（非 http(s) / 相对路径返回空串）。AdminAction `do` ∈ `moment-save|moment-delete|link-save|link-delete|album-save|album-delete|test-mail`。

**Tests:** Text 清洗与 URL 过滤；冒烟：面板四个标签渲染、保存说说后前台出现。

### Task K6: 主题说说 / 相册页、友链接入

**Files:** 主题 `page-moments.php`、`page-albums.php`、`assets/css/pages/moments.css`、`albums.css`（加入 `nimbus.css`）、`assets/js/modules/lightbox.js`（按 `[data-gallery]` 分组）、`tests/seed.php` 与 `tests/seed-kit.php`。

**Tests:** 冒烟：两个页面渲染、友链页显示后台数据；浏览器三套皮肤检查。

### Task K7: Sitemap

**Files:** `lib/Sitemap.php`、`Action.php`（sitemap）、`Plugin.php` 路由；`tests/unit/KitSitemapTest.php`。

**Tests:** XML 转义、`lastmod` 格式；冒烟：`/sitemap.xml` 200 且为 XML。

### Task K8: 文档、全量验证、独立审查、标签

README（插件与主题）、CHANGELOG、全部测试、独立代码审查并修复、打 `v1.1.0-rc1`。
