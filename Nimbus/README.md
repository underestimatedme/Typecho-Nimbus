# Nimbus

一个开源免费的 Typecho 主题。三套皮肤、精致动效、零构建、零插件依赖，上传即用。

> 设计灵感来自 Cuteen，代码完全独立实现。MIT 许可。

| 晴空 | 暮色 | 赛博朋克 |
| --- | --- | --- |
| ![sky](../docs/screenshots/home-sky.png) | ![dusk](../docs/screenshots/home-dusk.png) | ![cyber](../docs/screenshots/home-cyber.png) |

## 特性

- **三套皮肤**：晴空（白天）、暮色（夜间）、赛博朋克。一键切换，首屏无闪烁，可跟随系统。
- **首页**：Hero 大图（Ken Burns、视差、光斑、打字机副标题、波浪过渡）、置顶轮播、三种列表样式（图文交错 / 三栏网格 / 纯文字）、传统分页 / 加载更多 / 无限滚动。
- **自适应版面**：内容宽度随屏幕放大（最宽 1760），大屏不留大片空白；有头图的文章使用通栏沉浸式标题，超宽屏为「目录｜正文｜侧栏」三栏，正文保持易读行宽，图片、代码、表格铺满正文列。
- **文章页**：浮动目录、阅读进度、字数与阅读时长、代码高亮（行号、复制、折叠、语言标签）、KaTeX 公式、Mermaid 图表、图片灯箱、版权卡、打赏、分享、上一篇 / 下一篇、相关文章、过期提醒、密码保护美化。
- **评论**：楼中楼、博主标识、UA 显示、表情面板、Markdown 预览、Ctrl+Enter 提交。
- **页面模板**：归档时间线、友链卡片、关于页、404。
- **侧栏小部件**：作者卡、公告、热门文章、最新评论、标签云、分类、日历、站点信息、自定义 HTML，顺序可配。
- **全局**：悬浮胶囊导航、多级菜单、移动端抽屉、搜索弹窗（Ctrl/Cmd+K）、无刷新页面切换（View Transitions）、返回顶部进度环、哀悼模式、ICP / 公安备案、自定义头尾代码与 CSS / JS、CDN 前缀、SEO（OpenGraph / JSON-LD）。
- **零构建**：仓库里的文件就是运行文件，想改样式直接改 CSS 变量。

## 安装

1. 要求：Typecho 1.2.1+，PHP 7.4 – 8.4。
2. 下载本仓库，把 `Nimbus` 目录放到 `usr/themes/` 下（目录名必须是 `Nimbus`）。
3. 后台「控制台 → 外观」启用 Nimbus，然后在「设置外观」里按需配置。

可选：安装配套插件 **NimbusKit** 可解锁点赞、浏览量、即时搜索、AJAX 评论等互动功能（开发中）。未安装时这些功能自动隐藏，主题其余功能不受影响。

## 配置速查

后台「设置外观」分为六组：

| 分组 | 主要选项 |
| --- | --- |
| 站点 | Logo、Favicon、副标题、建站日期、备案号、作者头像 / 昵称 / 签名、社交链接、页脚 HTML |
| 首页 | Hero 开关 / 背景图 / 高度 / 标题 / 副标题 / 胶囊文字、置顶文章、列表样式、分页方式、摘要字数、默认缩略图池 |
| 文章 | 文章页布局（沉浸 / 经典）、字数 / 阅读时长、目录、代码行号 / 折叠、版权、打赏二维码、分享、相关文章、过期提醒、灯箱、公式、图表 |
| 侧栏 | 位置、小部件顺序、公告、热门数量、自定义小部件 |
| 外观 | 版面宽度（自适应 / 宽屏 / 标准）、亮 / 暗皮肤、跟随系统、允许切换、字体方案、圆角、动效强度、哀悼模式 |
| 高级 | CDN 前缀、自定义头 / 尾代码、自定义 CSS / JS、无刷新切换、自定义菜单、Gravatar 镜像、调试 |

**Hero 主标题**：用 `|` 分成两段，后段带流光效果，例如 `给时光|以生命`。

**社交链接**：每行 `名称|链接|图标`，图标可选 `github weibo bilibili twitter email rss qq wechat telegram zhihu link`。

**小部件顺序**：每行一个名称，行首 `#` 临时关闭。可用：`author notice hot recent-comments tags categories calendar site-info custom-html`。

**自定义菜单**（JSON）：

```json
[
  {"title": "分类", "url": "/category/", "icon": "folder", "children": [
    {"title": "技术", "url": "/category/tech/"}
  ]},
  {"title": "GitHub", "url": "https://github.com/you", "icon": "github"}
]
```

## 页面模板

在后台新建独立页面时选择对应模板：

- **归档**（`page-archives.php`）：按年月分组的时间线。
- **友链**（`page-links.php`）：在页面的「友链数据」自定义字段填入 JSON：

  ```json
  [{"name": "Typecho", "url": "https://typecho.org", "avatar": "https://typecho.org/favicon.ico", "desc": "轻量开源博客程序"}]
  ```
- **关于**（`page-about.php`）：大头像 + 社交 + 正文。

## 文章自定义字段

| 字段 | 说明 |
| --- | --- |
| `thumb` | 缩略图 URL，留空自动取正文首图，再回落到缩略图池 |
| `summary` | 自定义摘要 |
| `expireNotice` | 覆盖全局过期提醒天数，0 关闭 |
| `passwordHint` | 密码保护文章的提示语 |
| `nimbusHide` | 多选：隐藏标题 / 目录 / 版权 / 打赏 |

## 皮肤与定制

皮肤只是一组 CSS 变量，位于 `assets/css/theme-*.css`。改颜色直接改变量；想新增皮肤，复制一份文件并改选择器 `:root[data-theme="your-name"]`，再在后台「自定义 CSS」里引入或直接加入 `nimbus.css`。

URL 加 `?nimbus_theme=cyber` 可强制并记住某个皮肤，便于分享。

## 开发与测试

```bash
bash tests/serve.sh         # 启动本地测试站 http://localhost:8099（首次自动安装 Typecho 并灌入示例数据）
bash tests/dev-site.sh      # 仅重建 / 补齐测试站与示例数据
bash tests/lint.sh          # php -l + PHP 7.4 语法检查
php tests/run.php           # 单元测试
node --test tests/js/*.test.mjs
bash tests/css-vars.sh      # 皮肤变量完整性
bash tests/smoke.sh         # 页面渲染冒烟测试
```

## 致谢

- Typecho 团队
- Cuteen 主题（设计灵感）
- Prism、KaTeX、Mermaid、Noto Serif SC（见 `assets/vendor/*/LICENSE` 与 `assets/fonts/LICENSE.txt`）

## 许可

MIT © Nimbus Contributors
