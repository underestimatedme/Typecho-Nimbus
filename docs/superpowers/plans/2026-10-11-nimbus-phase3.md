# Nimbus 第 3 期 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:executing-plans.

**Spec:** `docs/superpowers/specs/2026-10-11-nimbus-phase3-design.md`

## Global Constraints

- PHP 7.4 语法；零构建；所有输出转义；主题不装插件可用。
- 第三方资源只在用户开启对应功能时加载（Live2D、Meting、GitHub API 卡片）。
- 每个任务结束：`bash tests/lint.sh && php tests/run.php && node --test tests/js/*.test.mjs && bash tests/css-vars.sh && bash tests/smoke.sh`；涉及数据表时再跑 `bash tests/multi-db.sh`。
- 提交信息中文，结尾 `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`。

## Review Focus

1. 短代码属性注入（`url="javascript:…"`、引号逃逸）→ 必须被过滤或转义。
2. `[hide]` 内容不得出现在未授权访客拿到的 HTML（含 RSS、摘要、搜索结果）里。
3. OAuth 回跳地址被改成外站、state 被重放或伪造 → 拒绝。
4. 伪造 `nimbus_gh` cookie 或在表单里改昵称邮箱冒充 GitHub 用户 → 签名校验失败、服务端覆盖字段。
5. 播放器在无刷新切换后重复初始化 → 不得出现两个音频同时播放。

## Tasks

- [ ] P1 插件：收藏夹 / 项目表、Store、Api、后台标签、每日浏览表；多数据库验证
- [ ] P2 主题模板：收藏夹、项目、留言板、横向时间线
- [ ] P3 短代码（含回复可见）
- [ ] P4 音乐：悬浮播放器 + 音乐页
- [ ] P5 分享海报（vendored qrcode-generator）
- [ ] P6 统计看板（主题 Stats + 插件 stats）
- [ ] P7 GitHub 登录（假 GitHub 服务器测试）
- [ ] P8 IP 属地（自研 xdb 读取器 + 测试用 xdb 生成器）
- [ ] P9 Live2D 可选加载
- [ ] P10 赛博朋克特效
- [ ] P11 配置导入导出
- [ ] P12 中英双语（最后做，覆盖所有新旧文案）
- [ ] P13 文档、独立审查、修复、`v1.2.0-rc1`
