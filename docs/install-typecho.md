# 安装 Typecho

Nimbus 是 [Typecho](https://typecho.org) 的主题。如果你还没有 Typecho 博客，按本页从零装好，再回到 [README](../README.md#快速开始) 安装主题和插件。全程大约 10 分钟。

> 只想先在自己电脑上看看效果？不用读本页，在仓库根目录运行 `bash tests/serve.sh` 即可，它会自动下载、安装 Typecho 并启用 Nimbus。

## 1. 准备环境

| 项目 | 要求 |
| --- | --- |
| PHP | 7.4 – 8.5（Typecho 本身支持更老的版本，但 Nimbus 需要 7.4+） |
| PHP 扩展 | `mbstring`、`json`、`dom`，以及下面任意一种数据库扩展；建议同时开启 `curl` 与 `openssl`（每日必应壁纸、GitHub 登录、SMTP 邮件会用到） |
| 数据库 | 三选一：**MySQL 5.5.3+ / MariaDB**（扩展 `pdo_mysql` 或 `mysqli`）、**SQLite**（扩展 `pdo_sqlite` 或 `sqlite3`，不需要数据库服务）、**PostgreSQL**（扩展 `pdo_pgsql` 或 `pgsql`） |
| Web 服务器 | Nginx、Apache、Caddy 均可，PHP 以 PHP-FPM 或 mod_php 运行 |

检查扩展：

```bash
php -m | grep -Ei 'mbstring|json|dom|pdo|mysql|sqlite|pgsql|curl|openssl'
```

使用 MySQL 时需要**先建好数据库**，安装程序不会替你创建：

```sql
CREATE DATABASE typecho DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

> 用宝塔、1Panel 等面板的话：在面板里新建一个 PHP 站点（选 PHP 7.4 或更高）并同时创建 MySQL 数据库即可，可以直接跳到第 2 步。

## 2. 下载并上传 Typecho

从 [Typecho 官方发布页](https://github.com/typecho/typecho/releases) 下载最新的 `typecho.zip`（1.2.1 或更高），解压到网站根目录，保证 `index.php`、`install.php`、`admin/`、`usr/`、`var/` 直接位于根目录下（不要多套一层 `typecho/` 目录）。

在服务器上可以直接用命令完成（把 `/var/www/blog` 换成你的网站目录）：

```bash
cd /var/www/blog
curl -LO https://github.com/typecho/typecho/releases/download/v1.2.1/typecho.zip
unzip typecho.zip && rm typecho.zip
```

然后让 PHP 进程对网站目录有写权限（安装程序要写入 `config.inc.php`，上传附件要写入 `usr/uploads`）。PHP 用户通常是 `www-data`（Debian / Ubuntu）、`nginx` 或 `www`（宝塔）：

```bash
sudo chown -R www-data:www-data /var/www/blog
```

## 3. 配置 Web 服务器

下面的配置同时开启了**伪静态**，让文章地址从 `/index.php/archives/1/` 变成 `/archives/1/`。

<details open>
<summary><b>Nginx</b></summary>

```nginx
server {
    listen 80;
    server_name blog.example.com;
    root /var/www/blog;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;   # 改成你的 PHP-FPM 地址
    }

    # 使用 SQLite 时，禁止直接下载数据库文件
    location ~* \.(db|sqlite|sqlite3)$ {
        deny all;
    }
}
```

改完执行 `sudo nginx -t && sudo systemctl reload nginx`。宝塔面板用户也可以在「网站 → 设置 → 伪静态」里直接选 `typecho` 模板。

</details>

<details>
<summary><b>Apache</b></summary>

确认启用了 `mod_rewrite`，站点配置里 `AllowOverride All`，然后在网站根目录放一个 `.htaccess`（之后在后台开启地址重写时，Typecho 也会尝试自动创建它）：

```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ /index.php/$1 [L]
</IfModule>
```

</details>

<details>
<summary><b>Caddy</b></summary>

```caddy
blog.example.com {
    root * /var/www/blog
    php_fastcgi unix//run/php/php8.2-fpm.sock
    file_server
}
```

Caddy 会自动申请 HTTPS 证书，`php_fastcgi` 已包含伪静态所需的规则。

</details>

正式上线建议开启 HTTPS（Nginx / Apache 可用 [Certbot](https://certbot.eff.org) 免费申请证书，面板一般有一键申请）。NimbusKit 的 GitHub 登录要求回调地址与站点协议一致。

## 4. 运行安装向导

浏览器打开 `https://你的域名/install.php`：

1. **欢迎页**：程序会检查 PHP 扩展和 `usr/uploads` 是否可写，没问题就点「我准备好了，开始下一步」。
2. **初始化配置**：
   - **数据库适配器**：选你的数据库，例如「Pdo 驱动 Mysql 适配器」。
   - **数据库前缀**：保持默认 `typecho_` 即可。
   - **MySQL / PostgreSQL**：填数据库地址（一般是 `localhost`）、端口、用户名、密码、数据库名；「高级选项」里的数据库编码保持 `utf8mb4`。
   - **SQLite**：只需确认「数据库文件路径」，默认在 `usr/` 下自动生成一个随机文件名。
3. **创建管理员帐号**：确认网站地址正确（带 `https://`），填用户名、登录密码和邮箱。密码留空会随机生成，安装完成页会显示出来，请记下。
4. 点「继续安装」，看到「安装成功」就完成了。

如果提示「安装程序无法自动创建 config.inc.php 文件」，说明根目录不可写：按页面提示在网站根目录手动新建 `config.inc.php`，粘贴页面给出的代码，再点「创建完毕，继续安装」。

## 5. 安装后的设置

1. 登录后台 `https://你的域名/admin/`。
2. **开启伪静态**：「设置 → 永久链接」，「是否使用地址重写功能」选「启用」，保存。若提示「重写功能检测失败」，回到第 3 步检查服务器配置。
3. （建议）同一页把「自定义文章路径」改成你喜欢的风格，例如 `/archives/{slug}.html`。**上线后尽量不要再改**，否则旧链接会失效。
4. 「设置 → 基本」里填好站点名称、描述；「设置 → 评论」按需开启审核。

到这里 Typecho 就装好了。接下来回到 [README · 快速开始](../README.md#快速开始) 安装 Nimbus 主题和 NimbusKit 插件。

## 常见问题

**打开首页正常，点文章却 404？** 伪静态没配好。开启地址重写前，地址形如 `/index.php/archives/1/`，Nginx 若只有 `location ~ \.php$` 而没有 `location /` 里的 `try_files`，这类地址也会 404。按第 3 步补全配置。

**安装时提示「数据库不存在」？** MySQL 需要先手动建库，见第 1 步的 SQL。

**安装时提示「上传目录无法写入」？** 执行第 2 步的 `chown`，或在面板里把 `usr/uploads` 权限设为 755 并属于 PHP 用户。

**后台登录后立刻又退出？** 多半是网站地址与实际访问地址不一致（如 `http` / `https`、带不带 `www`）。到「设置 → 基本」改成实际访问的地址；进不去后台时可直接修改数据库 `typecho_options` 表里 `siteUrl` 一行。

**换服务器 / 迁移？** 复制整个网站目录，导出导入数据库，并按新数据库信息修改根目录 `config.inc.php` 即可。
