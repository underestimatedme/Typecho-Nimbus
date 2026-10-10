# NimbusKit 插件 · 第 2 期设计文档

日期：2026-10-11
状态：项目所有者授权设计者自行决定细节，完成后汇报。

## 1. 目标

NimbusKit 是 Nimbus 主题的配套插件（MIT），提供需要服务端数据的互动功能。主题不装插件也完整可用；装上后通过主题的 `Nimbus\Kit` 适配层自动点亮对应功能。插件也不依赖主题：换主题后数据保留，接口仍可用。

环境：Typecho 1.2.1+，PHP 7.4 – 8.4，MySQL / SQLite / PostgreSQL。零 Composer 依赖。

### 1.1 功能范围

1. **浏览量**：文章页加载后前端上报一次，同一访客 6 小时内不重复计数；列表卡片、文章头、热门文章使用。
2. **点赞**：文章与说说都可点赞；按访客指纹（IP + UA 的加盐哈希）去重，一人一次；前端记住已赞状态。
3. **即时搜索**：搜索弹窗边输边出结果（标题优先、正文其次，最多 8 条）。
4. **AJAX 评论**：提交不刷新页面，成功后局部刷新评论区并定位到新评论；失败时在表单上提示 Typecho 返回的错误原因。
5. **邮件通知**：内置 SMTP 客户端（SSL / STARTTLS / 明文，AUTH LOGIN）。新评论通知站长；回复通知被回复者；可在设置页发送测试邮件。发送放在响应结束之后，不拖慢评论提交。
6. **说说**：后台发布短动态（文字 + 多张图片），前台「说说」页面以时间线展示，支持点赞与分页。
7. **友链管理**：后台增删改排序，前台友链页自动读取（替代页面自定义字段 JSON）。
8. **相册**：后台管理相册（标题、封面、简介、照片列表），前台「相册」页面网格展示，点击进入灯箱浏览。
9. **Sitemap**：`/sitemap.xml`，包含首页、文章、独立页、分类、标签。

不在本期：评论者 IP 属地（需要离线 IP 库，体积大，放到后续可选扩展）、分享海报、统计看板（第 3 期）。

## 2. 架构

```
NimbusKit/                      复制到 usr/plugins/NimbusKit
├── Plugin.php                  TypechoPlugin\NimbusKit\Plugin：activate / deactivate / config，注册路由、面板、钩子
├── Api.php                     对主题公开的静态门面（NimbusKit_Api）：VERSION、views、likes、hotPosts、links、moments、albums
├── Action.php                  前台接口路由 /nimbus-api/[endpoint]：view、like、search；/sitemap.xml
├── AdminAction.php             后台写操作 /action/nimbus-kit?do=...：说说 / 友链 / 相册的增删改、测试邮件
├── Hooks.php                   Widget_Feedback::finishComment：AJAX 响应、邮件通知
├── Store.php                   数据访问：stats、like_log、moments、links、albums 表的读写
├── Schema.php                  建表 SQL（mysql / sqlite / pgsql）
├── lib/                        无 Typecho 依赖的纯逻辑，可单测
│   ├── Smtp.php                SMTP 会话与 MIME 报文构造
│   ├── Mailer.php              通知邮件模板与收件人决策
│   ├── Visitor.php             访客指纹、浏览去重 cookie 规则
│   ├── Text.php                说说内容清洗（纯文本 + 换行 + 链接）、照片列表解析
│   └── Sitemap.php             XML 生成
├── panel.php                   后台面板：说说 / 友链 / 相册 / 统计 四个标签页
├── assets/admin.css、admin.js  面板样式与交互
└── README.md、LICENSE
```

主题侧新增：`page-moments.php`、`page-albums.php` 模板与样式，`modules/like.js`、浏览上报、评论 AJAX 流程、`Kit` 新方法、灯箱分组。

### 2.1 数据表（前缀沿用 Typecho 的 `table.` 前缀）

| 表 | 字段 |
| --- | --- |
| `nimbus_stats` | `cid` INT 主键、`type` VARCHAR(10)（post / moment）、`views` INT、`likes` INT。主键为 (`type`, `cid`)。 |
| `nimbus_like_log` | `id` 自增、`type`、`cid`、`visitor` CHAR(40)、`created` INT；唯一索引 (`type`, `cid`, `visitor`)。 |
| `nimbus_moments` | `id` 自增、`content` TEXT、`images` TEXT（JSON 数组）、`created` INT、`status` VARCHAR(10)（publish / hidden）。 |
| `nimbus_links` | `id` 自增、`name`、`url`、`avatar`、`description`、`sort` INT、`status`、`created`。 |
| `nimbus_albums` | `id` 自增、`title`、`cover`、`description` TEXT、`photos` TEXT（JSON：`[{url, caption}]`）、`sort` INT、`created`。 |

启用时 `CREATE TABLE IF NOT EXISTS`，重复启用安全。禁用默认保留数据；设置里有「禁用时删除全部数据」开关，默认关。

### 2.2 前台接口

基址 `{index}/nimbus-api/`，统一返回 `{"ok": true, "data": ...}` 或 `{"ok": false, "error": "原因"}`。

| 端点 | 方法 | 参数 | 返回 data |
| --- | --- | --- | --- |
| `view` | POST | `cid` | `{views}`；cookie `nimbus_v_{cid}` 6 小时内重复请求不计数 |
| `like` | POST | `cid`、`type`（post / moment） | `{likes, liked}`；已赞过返回当前数且不增加 |
| `search` | GET | `q`（2–40 字） | `[{title, url, excerpt, date}]` |

写接口校验来源：`Origin` 或 `Referer` 的主机必须与站点一致，否则 403。只接受已发布、无密码的文章。功能可在插件设置里单独关闭，关闭后接口返回 `{"ok":false,"error":"disabled"}`，主题前端据此隐藏。

`/sitemap.xml`：注册在 `index` 路由之后，`Content-Type: application/xml`。

### 2.3 主题适配（`Nimbus\Kit`）

已有：`enabled()`、`apiUrl()`、`views()`、`likes()`、`hotPosts()`、`links()`。新增：

- `moments(int $page, int $size): ?array` → `['items' => [...], 'total' => int]`，每项 `id, content(已清洗 HTML), images[], created, likes`。
- `albums(): ?array` → 每项 `id, title, cover, description, photos[{url, caption}]`。
- `features(): array` → 插件开启的功能名单，主题据此决定是否渲染点赞按钮等。

`NimbusKit_Api::VERSION = '1.0.0'`，主题要求 ≥ 1.0.0。

### 2.4 AJAX 评论流程

1. 前端在表单提交时带 `X-Requested-With: XMLHttpRequest` 用 fetch 提交到原评论地址。
2. 插件的 `finishComment` 钩子检测到 Ajax 请求，输出 `{"ok":true,"data":{"coid","status","message"}}` 并结束（`status` 为 waiting 时提示"等待审核"）。
3. 前端成功后重新 fetch 当前文章页，只替换 `#comments` 区块，滚动并高亮新评论。
4. Typecho 校验失败会抛异常输出错误页（403 / 500 HTML）；前端解析错误页正文作为提示，保留用户输入。
5. 插件未启用或接口异常时回退为普通表单提交。

### 2.5 邮件

设置项：开关、SMTP 主机、端口、加密方式（ssl / tls / none）、用户名、密码、发件人名称、发件地址、站长收件地址（默认管理员邮箱）、是否通知站长、是否通知被回复者。

规则：
- 新评论且评论者不是站长 → 通知站长。
- 评论有父评论、父评论邮箱有效、且与当前评论者不同 → 通知父评论作者（仅当新评论状态为 approved）。
- 邮件为 HTML + 纯文本双版本，包含站点名、文章标题、评论内容、原文链接。
- 发送在 `register_shutdown_function` 中执行：先刷出缓冲并 `fastcgi_finish_request()`（如可用），再连接 SMTP；失败只写 `error_log`，不影响评论。
- 「发送测试邮件」按钮走后台动作，同步发送并显示结果。

### 2.6 后台面板

「管理 → Nimbus」，四个标签：说说（列表 + 发布 / 编辑表单）、友链（列表 + 表单 + 排序数字）、相册（列表 + 表单，照片每行 `URL|说明`）、统计（浏览量 / 点赞 Top 20）。写操作提交到 `/action/nimbus-kit`，校验管理员权限与 Typecho 安全 token。

## 3. 安全

- 所有输出转义；说说内容只保留纯文本、换行与自动识别的 http(s) 链接。
- 后台动作：`$user->pass('administrator')` + `$security->protect()`。
- URL 字段只接受 `http(s)://` 与站内相对路径。
- SMTP 密码存于插件配置（Typecho options 表），设置页输入框为 password 类型。
- 访客指纹 = `sha1(盐 + IP + UA)`，盐在启用时随机生成并保存，不存原始 IP。

## 4. 测试

- 纯逻辑单测：Smtp 报文（编码、换行、头部）、Mailer 收件人决策、Visitor 去重、Text 清洗与照片解析、Sitemap XML、Schema 生成。接入现有 `php tests/run.php`。
- 测试站：`tests/dev-site.sh` 自动链接插件并通过后台动作启用，灌入说说 / 友链 / 相册示例。
- 冒烟：view / like / search 接口、来源校验、sitemap、说说 / 相册 / 友链页、后台面板渲染、AJAX 评论成功与失败。
- 邮件：`tests/fake-smtp.py` 本地假 SMTP 服务器，验证评论后确实收到通知与测试邮件。
- 浏览器：点赞动画与计数、即时搜索、AJAX 评论、说说与相册页三套皮肤。
