# Nimbus 第 3 期设计文档

日期：2026-10-11
状态：范围由项目所有者确定（见 `docs/ROADMAP.md`，聊天室不做）；细节由设计者决定，完成后汇报。

版本：主题 1.2.0，NimbusKit 1.1.0（主题仍兼容 NimbusKit 1.0，新功能按插件方法是否存在降级）。

## 1. 功能与归属

| # | 功能 | 主题 | 插件 | 不装插件时 |
| --- | --- | --- | --- | --- |
| 1 | 收藏夹 | `page-bookmarks.php` | `nimbus_bookmarks` 表 + 后台标签 | 页面提示需启用插件 |
| 2 | 项目展示 | `page-projects.php` | `nimbus_projects` 表 + 后台标签 | 同上 |
| 3 | 留言板 | `page-messages.php` | — | 完整可用 |
| 4 | GitHub 登录留言 | 评论表单显示登录态 | OAuth 路由、签名 cookie、可选「仅 GitHub 用户可评论」 | 不显示登录按钮 |
| 5 | 音乐页与播放器 | 全站悬浮播放器 + `page-music.php` | — | 完整可用 |
| 6 | Live2D 看板娘 | 可选加载 | — | 完整可用 |
| 7 | 横向时间线 | `page-timeline.php` | — | 完整可用 |
| 8 | 短代码 | 正文处理 | — | 完整可用 |
| 9 | 分享海报 | 文章页 | — | 完整可用 |
| 10 | 统计看板 | `page-stats.php` | 每日浏览量表 + 接口数据 | 显示文章 / 评论类统计，无浏览量 |
| 11 | 评论者 IP 属地 | 评论头显示省份 | ip2region xdb 读取 | 不显示 |
| 12 | 中英双语 | 前台全部文案 | 前台接口与插件页文案 | — |
| 13 | 配置导入导出 | 后台设置页 | — | — |
| 14 | 赛博朋克特效增强 | 皮肤特效开关 | — | — |

## 2. 设计要点

### 2.1 收藏夹与项目（插件）

- `nimbus_bookmarks`：`id, group_name, name, url, icon, description, sort, status, created`。
- `nimbus_projects`：`id, name, description, cover, url, repo, tags`（逗号分隔）`, sort, status, created`。
- 后台面板增加「收藏夹」「项目」标签，表单与列表同说说 / 友链风格。
- `Api::bookmarks()` 返回按分组聚合的数组（分组按首次出现的最小排序值排序）；`Api::projects()`。
- 前台：收藏夹分组标签栏 + 即时过滤输入框 + 卡片网格；无图标时用首字母色块（不调用任何第三方图标服务）。项目卡片含封面、技术栈标签、演示 / 源码链接。
- 启用时 `CREATE TABLE IF NOT EXISTS`，已有站点升级只需重新启用插件（配置会自动保留）。

### 2.2 留言板

`page-messages.php`：独立页 + 评论。表单置顶，留言以卡片墙展示（顶层留言网格排列，回复折叠在卡片内）。复用评论模块全部能力（AJAX、表情、GitHub 登录）。

### 2.3 GitHub 登录

- 插件设置：`githubClientId`、`githubClientSecret`、`githubOnly`（仅 GitHub 用户可评论，站长除外）。
- 路由：`/nimbus-api/github-login?back=URL` → 生成 `state`（HMAC 签名，含时间戳与回跳地址，10 分钟有效）→ 跳转 GitHub 授权；`/nimbus-api/github-callback` → 校验 state → 服务端换取 token → 读取 `/user` → 写入签名 cookie `nimbus_gh`（`id, login, name, avatar, url, mail`，30 天）→ 回跳；`/nimbus-api/github-logout`。
- 回跳地址只允许本站同源路径，防开放重定向。
- 邮箱：使用 GitHub 公开邮箱；没有时用 `{id}+{login}@users.noreply.github.com`。主题对这类邮箱直接使用 GitHub 头像。
- 评论提交时由插件 `Widget_Feedback::comment` 过滤器用 cookie 里的身份覆盖 `author/mail/url`，前端字段不可伪造；`githubOnly` 开启时无有效 cookie 的访客评论被拒绝并给出提示。
- 测试：GitHub 地址可通过隐藏配置项替换为本地假服务器（`tests/fake-github.py`）。

### 2.4 音乐

- 主题设置（外观分组）：`MusicEnable`、`MusicSource`（`list` 自定义 / `meting` 接口）、`MusicList`（每行 `歌名|歌手|音频URL|封面URL|歌词URL`）、`MusicMetingApi`（如 `https://api.example.com/meting/?server=:server&type=playlist&id=:id`）、`MusicMetingServer`、`MusicMetingId`、`MusicAutoplay`（默认否）。
- 悬浮播放器：左下角胶囊（封面旋转、播放 / 暂停、下一首、进度、展开歌单）；位于 `#n-main` 之外，无刷新切换页面时不中断播放；记住音量与上次曲目。
- 音乐页：大封面、频谱可视化（Web Audio，跨域失败时退化为动画）、LRC 歌词滚动、歌单列表。与悬浮播放器共用同一个播放实例。

### 2.5 Live2D

外观设置：`Live2dEnable`（默认关）、`Live2dScript`（默认 `https://fastly.jsdelivr.net/npm/live2d-widgets@1.0.1/dist/autoload.js`）、`Live2dMobile`（默认手机不显示）。空闲时延迟加载，失败静默。后台说明写明：挂件为 GPL-3、Cubism SDK 为 Live2D 专有许可、示例模型仅限非商业用途；Nimbus 不打包任何相关文件。

### 2.6 横向时间线

`page-timeline.php`：全部文章按时间排列在一条横向河流上，年份节点、卡片上下交错；支持拖拽、滚轮横向滚动、键盘左右键，移动端改为纵向。

### 2.7 短代码

在 `Content::renderFrom` 的 DOM 处理前解析（兼容 Markdown 包裹的 `<p>[tip]</p>`）：

| 短代码 | 效果 |
| --- | --- |
| `[tip type="info|success|warning|danger"]…[/tip]` | 提示框 |
| `[collapse title="…" open]…[/collapse]` | 折叠块（`<details>`） |
| `[tabs][tab title="A"]…[/tab][/tabs]` | 标签页 |
| `[button url="…" type="primary|ghost"]文字[/button]` | 按钮（URL 过滤） |
| `[video src="…" poster="…"]` | HTML5 视频 |
| `[bilibili bv="BV…" page="1"]` | B 站播放器（BV 号格式校验） |
| `[github repo="owner/name"]` | 仓库卡片，前端读取 GitHub API，本地缓存 6 小时 |
| `[hide]…[/hide]` | 回复可见：已登录、或记住的邮箱在本文有已通过的评论时显示，否则服务端不输出内容 |

属性值全部转义；未知短代码原样保留；代码块内的短代码不解析。

### 2.8 分享海报

文章页分享菜单增加「生成海报」：Canvas 绘制标题、摘要、作者、日期、站点名与二维码（内置 MIT 许可的 qrcode-generator），封面图可跨域读取时绘入，否则用皮肤渐变；弹窗预览，可下载 PNG。

### 2.9 统计看板

- 插件新增 `nimbus_daily`（`day` INT 主键，形如 20261011；`views`），浏览上报时累加。`Api::stats()` 返回近 30 天浏览量、总浏览、总点赞、热门 Top 10。
- 主题 `core/Stats.php` 计算文章 / 评论 / 分类 / 标签总数、近 12 个月发文与评论数、分类分布、近一年发文热力图。
- `page-stats.php`：数字卡片（滚动计数）、折线 / 柱状 / 环形 / 热力图，全部手写 SVG，随皮肤配色。

### 2.10 IP 属地

插件读取 `NimbusKit/data/ip2region.xdb`（用户自行下载，Apache-2.0 / MIT）。自研 IPv4 读取器，按格式查找，结果只显示省份（国外显示国家），内网 IP 显示「本地」。设置项 `ipRegion` 开关；文件缺失时功能自动关闭并在面板提示下载方法。主题评论头通过 `Kit::region(ip)` 显示。

### 2.11 中英双语

- 主题 `core/I18n.php`：`I18n::t(string $zh, array $args = [])`，语言包 `lang/en.php`（以中文原文为键）。模板中的前台文案全部改为调用；JS 文案通过 `NimbusConfig.i18n` 下发。
- 设置 `Language`：`zh`、`en`、`auto`（按浏览器 `Accept-Language`）。`<html lang>` 随之变化。
- 范围：前台全部界面文案与插件前台接口提示。后台设置页仍为中文（用户群主要为中文站长）。

### 2.12 配置导入导出

后台主题设置页顶部增加「导出配置 / 导入配置」：导出把当前表单值序列化为 JSON 下载；导入读取 JSON 文件填回表单，用户确认后点「保存设置」。纯前端，无新增服务端接口。

### 2.13 赛博朋克增强

外观设置 `CyberEffects`（多选）：字符雨背景（Hero 区 Canvas）、标题故障悬停、霓虹描边小部件、鼠标霓虹拖尾、副标题乱码解码动画。仅在 cyber 皮肤生效，尊重「动效强度」与系统减少动态设置。

## 3. 测试

- 单测：短代码解析（含转义、嵌套、代码块跳过、hide 两种状态）、xdb 读取（测试内生成 xdb 文件）、OAuth state 签名与回跳校验、收藏夹分组、i18n 查找与回落、Stats 聚合。
- 冒烟：新增 6 个页面模板、短代码文章、GitHub 登录流程（假 GitHub 服务器）、英文界面。
- 多数据库脚本覆盖新增两张表。
- 浏览器：三套皮肤下的新页面、播放器跨页不断、海报生成、导入导出。
