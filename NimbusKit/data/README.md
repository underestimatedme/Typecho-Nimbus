# IP 属地数据

评论者 IP 属地功能需要 ip2region 的 IPv4 数据文件，NimbusKit 不附带（约 11 MB，按其许可单独分发）。

1. 打开 https://github.com/lionsoul2014/ip2region ，在 `data/` 目录下载 `ip2region.xdb`（IPv4 版本）。
2. 放到本目录：`usr/plugins/NimbusKit/data/ip2region.xdb`。
3. 在「控制台 → 插件 → NimbusKit 设置」确认「评论者 IP 属地」为「显示」。

只显示省份（国外显示国家），内网地址显示「本地」，不会显示具体 IP。数据文件缺失时功能自动关闭。
