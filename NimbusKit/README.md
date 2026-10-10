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
| Sitemap | `/sitemap.xml`，含首页、文章、独立页、分类、标签 |

## 安装

1. 要求 Typecho 1.2.1+、PHP 7.4 – 8.4，数据库 MySQL / SQLite / PostgreSQL 均可。
2. 把 `NimbusKit` 目录放到 `usr/plugins/` 下（目录名必须是 `NimbusKit`）。
3. 后台「控制台 → 插件」启用。启用时自动建表，重复启用安全。
4. 在插件设置里勾选要开启的功能、填写邮件配置。

说说、友链、相册与统计在「管理 → Nimbus」中管理，测试邮件也在那里发送。

前台展示说说和相册：新建独立页面，「自定义模板」分别选择「说说」「相册」（Nimbus 主题提供）。

## 邮件配置示例

| 服务商 | 服务器 | 端口 | 加密 | 密码 |
| --- | --- | --- | --- | --- |
| QQ 邮箱 | smtp.qq.com | 465 | SSL | 邮箱设置里生成的授权码 |
| 163 邮箱 | smtp.163.com | 465 | SSL | 客户端授权码 |
| Gmail | smtp.gmail.com | 587 | STARTTLS | 应用专用密码 |
| Outlook | smtp.office365.com | 587 | STARTTLS | 账号密码或应用密码 |

邮件在评论响应发回浏览器之后才发送，不会拖慢评论提交；发送失败只写入 PHP 错误日志，不影响评论。

## 接口

基址 `{站点}/index.php/nimbus-api/`（开启伪静态时去掉 `index.php`）。返回 `{"ok": true, "data": ...}` 或 `{"ok": false, "error": "..."}`。

| 端点 | 方法 | 参数 | 说明 |
| --- | --- | --- | --- |
| `view` | POST | `cid` | 记录一次浏览 |
| `like` | POST | `cid`、`type`（post / moment） | 点赞，已赞过返回当前数 |
| `search` | GET | `q`（2–40 字） | 最多 8 条结果 |

写接口要求请求来自本站（校验 Origin / Referer）。

## 主题开发者

主题通过静态门面 `NimbusKit_Api` 读取数据：`VERSION`、`features()`、`views($cid)`、`likes($cid)`、`hotPosts($limit)`、`links()`、`moments($page, $size)`、`albums()`。功能关闭时对应方法返回 `null`。

## 数据与卸载

数据表：`nimbus_stats`、`nimbus_like_log`、`nimbus_moments`、`nimbus_links`、`nimbus_albums`（带 Typecho 表前缀）。

禁用插件默认**保留**数据。若要彻底清除，先在插件设置里把「禁用插件时」改为「删除全部数据」并保存，再禁用。
