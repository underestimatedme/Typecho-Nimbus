# Nimbus

开源免费的 Typecho 主题。设计灵感来自 Cuteen，代码独立实现，MIT 许可。

- [`Nimbus/`](Nimbus/) — 主题本体，复制到 `usr/themes/Nimbus` 即可使用。详见 [Nimbus/README.md](Nimbus/README.md)。
- `NimbusKit/` — 配套插件（第 2 期，开发中）：点赞、浏览量、即时搜索、AJAX 评论、说说、友链管理等。
- [`docs/`](docs/) — 设计文档与用户手册。
- [`tests/`](tests/) — 本地测试站脚本、单元测试、冒烟测试。

## 快速开始

```bash
bash tests/dev-site.sh   # 需要 php >= 7.4（含 sqlite 扩展）与 curl
```

打开 http://localhost:8099 即可预览（后台 admin / admin123）。
