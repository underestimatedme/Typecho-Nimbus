# NimbusKit

[Nimbus](../Nimbus/) 主题的配套插件，为博客加上需要服务端数据的互动功能。MIT 许可。

主题不装插件也完整可用；装上后主题会自动点亮对应功能。插件也不依赖主题：换主题后数据保留，接口照常可用。

## 功能

| 功能 | 说明 |
| --- | --- |
| 浏览量 | 文章页加载后上报，同一访客 6 小时内不重复计数；热门文章按浏览量排序 |
| 点赞 | 文章与说说都可点赞，同一访客只计一次（不保存原始 IP） |
| 即时搜索 | 搜索弹窗边输入边出结果，标题命中优先 |
| AJAX 评论 | 提交不刷新页面；被拒时显示原因并保留输入 |
| 邮件通知 | 新评论通知站长，回复通知被回复者；内置 SMTP，支持 SSL / STARTTLS |
| 说说 | 后台发布文字 + 多图动态，前台时间线展示 |
| 友链 | 后台管理，友链页自动读取 |
| 相册 | 后台管理相册与照片，前台网格 + 灯箱浏览 |
| 收藏夹 | 后台分组管理常用站点，前台网址导航页（分组标签 + 即时过滤） |
| 项目 | 后台管理项目（封面、技术栈、演示 / 源码链接），前台项目展示页 |
| GitHub 登录评论 | 访客用 GitHub 账号登录后评论，昵称、头像、主页自动填写且不可伪造；可设「仅 GitHub 用户可评论」 |
| IP 属地 | 评论头显示省份（国外显示国家），需自行放入 ip2region 数据文件 |
| 来源与作品点击 | 可选、默认关闭；按来源汇总到访与配置目标的链接点击，后台查看近 14 天 |
| 浏览趋势 | 按天记录浏览量，供主题统计看板绘制近 30 天趋势 |
| Sitemap | `/sitemap.xml`，含首页、文章、独立页、分类、标签 |

## 安装

1. 要求 Typecho 1.2.1+、PHP 7.4 – 8.5，数据库 MySQL / SQLite / PostgreSQL 均可。
2. 把 `NimbusKit` 目录放到 `usr/plugins/` 下（目录名必须是 `NimbusKit`）。
3. 后台「控制台 → 插件」启用。启用时自动建表，重复启用安全。
4. 在插件设置里勾选要开启的功能、填写邮件配置。

说说、友链、相册、收藏夹、项目与统计在「管理 → Nimbus」中管理，测试邮件也在那里发送。

**从 1.0 升级**：覆盖目录后在「控制台 → 插件」禁用再启用一次 NimbusKit，以创建新表并注册新钩子。设置会自动保留。

前台展示：新建独立页面，「自定义模板」选择「说说」「相册」「收藏夹」「项目」「统计」（Nimbus 主题提供）。

## 来源与作品点击

更新主题和插件后，在插件设置中开启「来源与作品点击统计」并保存，即可为旧数据库创建统计表，无需禁用插件。每行配置 `英文标识|显示名称|公开URL`；后台「管理 → Nimbus → 统计」查看近 14 天汇总。关闭后停止采集，保留已有记录。

30 分钟 Cookie 去重，统计的是浏览器到访和链接点击，不能当作独立访客、注册或购买。来源缺失归为「直接 / 来源不可知」；不追溯历史。尊重 DNT/GPC，已登录 Typecho 用户不计入。不保存 IP、User-Agent、完整来源 URL 或页面查询参数。配置方法与限制见 [统计说明](../docs/growth-analytics.md)。

## 邮件配置示例

| 服务商 | 服务器 | 端口 | 加密 | 密码 |
| --- | --- | --- | --- | --- |
| QQ 邮箱 | smtp.qq.com | 465 | SSL | 邮箱设置里生成的授权码 |
| 163 邮箱 | smtp.163.com | 465 | SSL | 客户端授权码 |
| Gmail | smtp.gmail.com | 587 | STARTTLS | 应用专用密码 |
| Outlook | smtp.office365.com | 587 | STARTTLS | 账号密码或应用密码 |

邮件在评论响应发回浏览器之后才发送，不会拖慢评论提交；发送失败只写入 PHP 错误日志，不影响评论。

## GitHub 登录评论

1. 打开 GitHub「Settings → Developer settings → OAuth Apps → New OAuth App」。
2. Homepage URL 填站点地址；Authorization callback URL 填插件设置页提示的地址（形如 `https://你的站点/index.php/nimbus-api/github-callback`，开启伪静态时没有 `index.php`）。
3. 把生成的 Client ID 与 Client Secret 填进插件设置并保存，评论区就会出现「GitHub 登录」按钮。

评论表单里的隐藏昵称是 `github-{id}`，入库时换成 GitHub 显示名；显示名与本站注册用户重名时会变成「显示名 @login」。

安全设计：登录请求带签名和 10 分钟有效期，并与当前浏览器绑定；登录后只回跳到本站地址；GitHub 的访问令牌只在服务端用一次、不保存。身份保存在签名 cookie（30 天）里，评论时由服务端覆盖昵称、邮箱、网址。没有公开邮箱的用户使用 `{id}+{login}@users.noreply.github.com`，主题会直接显示其 GitHub 头像。

## IP 属地

把 [ip2region](https://github.com/lionsoul2014/ip2region) 的 IPv4 数据文件 `ip2region.xdb` 放到 `usr/plugins/NimbusKit/data/`。只显示省份或国家，内网地址显示「本地」，不展示 IP 本身。数据文件不随插件分发，缺失时功能自动关闭。

## 接口

基址 `{站点}/index.php/nimbus-api/`（开启伪静态时去掉 `index.php`）。返回 `{"ok": true, "data": ...}` 或 `{"ok": false, "error": "..."}`。

| 端点 | 方法 | 参数 | 说明 |
| --- | --- | --- | --- |
| `growth` | POST | `kind`（entry / goal）、`utm`、来源主机名 `referrer`、目标标识 `goal` | 仅开启时采集；返回是否计入，不公开汇总 |
| `view` | POST | `cid` | 记录一次浏览 |
| `like` | POST | `cid`、`type`（post / moment） | 点赞，已赞过返回当前数 |
| `search` | GET | `q`（2–40 字） | 最多 8 条结果 |

写接口要求请求来自本站（校验 Origin / Referer）。

## 主题开发者

主题通过静态门面 `NimbusKit_Api` 读取数据：`VERSION`、`features()`、`views($cid)`、`likes($cid)`、`hotPosts($limit)`、`links()`、`moments($page, $size)`、`albums()`、`bookmarks()`、`projects()`、`stats()`、`growth()`、`region($ip)`、`github($back)`。功能关闭时对应方法返回 `null`。

前台 GitHub 登录相关端点：`github-login?back=`、`github-callback`、`github-logout?back=`。

## 数据与卸载

数据表：`nimbus_stats`、`nimbus_like_log`、`nimbus_moments`、`nimbus_links`、`nimbus_albums`、`nimbus_bookmarks`、`nimbus_projects`、`nimbus_daily`、`nimbus_growth`（带 Typecho 表前缀）。

禁用插件默认**保留**数据。若要彻底清除，先在插件设置里把「禁用插件时」改为「删除全部数据」并保存，再禁用。
