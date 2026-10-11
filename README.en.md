<p align="right"><a href="README.md">简体中文</a> | <strong>English</strong></p>

<p align="center">
  <img src="docs/screenshots/banner.jpg" alt="Nimbus — a magazine-style theme for Typecho" width="100%">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/license-MIT-7c3aed?style=flat-square" alt="MIT">
  <img src="https://img.shields.io/badge/Typecho-1.2.1%2B-3b82f6?style=flat-square" alt="Typecho 1.2.1+">
  <img src="https://img.shields.io/badge/PHP-7.4%20~%208.5-777bb4?style=flat-square&logo=php&logoColor=white" alt="PHP 7.4 ~ 8.5">
  <img src="https://img.shields.io/badge/DB-MySQL%20%7C%20SQLite%20%7C%20PostgreSQL-0ea5e9?style=flat-square" alt="MySQL | SQLite | PostgreSQL">
  <img src="https://img.shields.io/badge/build-zero-ec4899?style=flat-square" alt="Zero build">
  <img src="https://img.shields.io/badge/i18n-中文%20%7C%20English-f59e0b?style=flat-square" alt="Chinese | English">
</p>

<p align="center">
  <b>Nimbus</b> is a free, open-source theme for Typecho, with the companion <b>NimbusKit</b> plugin.<br>
  Magazine-style home and post pages, three skins, tutorial series, an analytics dashboard, a timeline, a music player, and more. Upload and use it—no build step required.<br>
  <sub>A magazine-style, zero-build Typecho theme with three skins, tutorials, stats, timeline and a companion plugin. MIT licensed.</sub>
</p>

<p align="center">
  <a href="#quick-start">Quick start</a> ·
  <a href="docs/install-typecho.md">Install Typecho (Chinese)</a> ·
  <a href="#screenshots">Screenshots</a> ·
  <a href="#features-at-a-glance">Features</a> ·
  <a href="docs/user-guide.md">User guide (Chinese)</a> ·
  <a href="Nimbus/CHANGELOG.md">Changelog</a>
</p>

---

## Three skins, one-click switching

<img src="docs/screenshots/skins.jpg" alt="Clear Sky, Dusk and Cyberpunk skins" width="100%">

**Clear Sky** is designed for daytime, **Dusk** for nighttime, and **Cyberpunk** adds code rain, neon outlines and glitch effects on headings. The theme can follow your system's light or dark appearance without a flash on first paint. Each skin is a set of CSS variables, so you can customize the colors by editing the variables.

## Highlights

- **Magazine-style homepage**: Aurora cover, oversized gradient title, 3D article cards that tilt with the pointer, a scrolling category bar and a Bento featured grid. Set the Hero background to **Bing's daily wallpaper** to change it automatically each day.
- **Immersive reading**: Cover stage and floating article card, a left action bar, floating table of contents, numbered headings and pull quotes. Includes syntax highlighting, KaTeX formulas, Mermaid diagrams and an image lightbox.
- **Tutorial series**: Mark a category as a tutorial to show course cards and learning progress on the homepage, a course index on the category page, and chapter navigation on posts.
- **Analytics dashboard**: View trends, monthly posts and comments, category distribution and a writing heatmap. Select a heatmap day to see what you wrote.
- **Timeline**: Posts, tutorials and projects flow together in a vertical timeline that can be filtered by type.
- **Site-wide music player**: Music continues across page changes. The music page includes a vinyl record, spectrum visualizer and scrolling lyrics.
- **Fast**: Styles are combined into one file, script modules are preloaded, and page changes use View Transitions. The local homepage's first screen loads in under 0.2 seconds.
- **No build step or dependencies**: The files in the repository are ready to run. No Node, Composer or bundler is needed, and the theme works fully without the plugin.

## Screenshots

<img src="docs/screenshots/post-cyber.jpg" alt="A post page using the Cyberpunk skin, with a background of real code characters" width="100%">

<p align="center"><sub>The Cyberpunk post page. Its character rain is made of real Python, Go and Rust code, displayed vertically.</sub></p>

<table>
  <tr>
    <td width="50%"><img src="docs/screenshots/home-bento.jpg" alt="Bento featured grid on the homepage"><p align="center"><b>Bento highlights</b><br><sub>Featured posts, author, writing rhythm, latest notes and now playing</sub></p></td>
    <td width="50%"><img src="docs/screenshots/post-reading.jpg" alt="Post reading page"><p align="center"><b>Reading experience</b><br><sub>Floating article card, action bar, right-side table of contents, tables and quotes</sub></p></td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/tutorial.jpg" alt="Tutorial series index"><p align="center"><b>Tutorial series</b><br><sub>Course cover, chapter index and learning progress</sub></p></td>
    <td><img src="docs/screenshots/stats.jpg" alt="Analytics dashboard"><p align="center"><b>Analytics dashboard</b><br><sub>Hand-drawn SVG charts that adapt to the active skin</sub></p></td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/timeline.jpg" alt="Timeline"><p align="center"><b>Timeline</b><br><sub>Sticky year labels, alternating entries and a center line lit by scrolling</sub></p></td>
    <td><img src="docs/screenshots/music.jpg" alt="Music page"><p align="center"><b>Music</b><br><sub>Vinyl record, spectrum and LRC lyrics, shared with the floating player</sub></p></td>
  </tr>
</table>

### Looks just as good on mobile

<img src="docs/screenshots/mobile.jpg" alt="Mobile homepage, post page and timeline" width="100%">

## Features at a glance

<table>
  <tr><th width="50%">Nimbus theme (works on its own)</th><th width="50%">NimbusKit plugin (optional; enables extra features)</th></tr>
  <tr valign="top"><td>

- Three skins; follow the system or select one
- Magazine or classic homepage, with three list styles
- Pagination, infinite scroll or load-more button (configurable in the dashboard)
- Magazine, immersive or classic post layouts
- Floating table of contents, reading progress, word count and estimated reading time
- Syntax highlighting (line numbers, copy and collapse), KaTeX and Mermaid
- Tutorial series and saved learning progress
- Comments with threaded replies, emoji and Markdown preview
- Page templates: archive, links, about, guestbook, timeline, music, stats, tutorials and 404
- Shortcodes: callouts, collapsible sections, tabs, buttons, video, Bilibili, GitHub cards and reply-to-view content
- Share posters with downloadable QR codes
- Daily Bing Hero backgrounds
- Cyberpunk effects and optional Live2D character
- Chinese and English frontend; import and export settings
- Page transitions without reloads, search dialog (Ctrl/Cmd+K) and SEO

</td><td>

- View counts (deduplicated over six hours) and likes
- Instant search with results as you type
- AJAX comments that preserve input if submission fails
- Email notifications (built-in SMTP with SSL / STARTTLS)
- Short posts with text and multiple images
- Links, albums, bookmarks and project showcase
- GitHub sign-in for comments; optionally restrict comments to GitHub users
- Commenter IP geolocation using the offline ip2region database
- Daily view counts for the analytics dashboard
- `/sitemap.xml`
- Unified dashboard under **Manage → Nimbus**
- Data is retained when you change themes

</td></tr>
</table>

## Quick start

### Don't have Typecho yet?

Nimbus requires Typecho 1.2.1+ and PHP 7.4+. Choose MySQL, SQLite or PostgreSQL for the database. The basic Typecho installation steps are:

1. Prepare PHP and a database. If you use MySQL, create an empty database first.
2. Download a package from the [Typecho releases page](https://github.com/typecho/typecho/releases), extract it into your web root, and make sure PHP can write to the required directories.
3. Open `https://your-domain/install.php` in a browser, follow the setup wizard, enter your database details and create an administrator account.
4. After installation, enable URL rewriting under **Settings → Permalinks** in the dashboard.

For detailed instructions on downloading and extracting Typecho, database settings, Nginx / Apache / Caddy rewrite rules and common issues, see the **[Typecho installation guide (Chinese)](docs/install-typecho.md)**.

### Install Nimbus on your blog

1. Download this repository with `git clone` or the **Download ZIP** button on GitHub.
2. Copy `Nimbus/` to `usr/themes/Nimbus` in your Typecho installation. The directory must be named `Nimbus`.
3. In the dashboard, go to **Console → Appearance**, enable Nimbus, then configure it under **Settings → Appearance**.
4. (Optional) Copy `NimbusKit/` to `usr/plugins/NimbusKit`, then enable it under **Console → Plugins**. It creates its tables on activation; reactivation is safe, and data is retained when you deactivate it.

To create common pages such as an archive, links, stats, timeline or tutorials, add a standalone page in the dashboard and select its template. See the [theme guide (Chinese)](Nimbus/README.md#页面模板) for details.

### Preview locally

```bash
bash tests/serve.sh
```

Requires PHP 7.4+ (including `pdo_sqlite`) and curl. On the first run, the script downloads and installs Typecho 1.2.1, enables NimbusKit and loads sample data. Open http://localhost:8099 to preview. The dashboard is at `/admin/` (admin / admin123).

## Requirements

| Component | Requirement |
| --- | --- |
| Typecho | 1.2.1 or later |
| PHP | 7.4 – 8.5 |
| Database | MySQL / MariaDB, SQLite or PostgreSQL (all three have been tested with the plugin) |
| Browser | Recent Chrome, Edge, Safari or Firefox; browsers without View Transitions fall back to regular navigation |

## Documentation

| Document | Contents |
| --- | --- |
| [Typecho installation guide (Chinese)](docs/install-typecho.md) | Set up Typecho from scratch: requirements, upload, rewrite rules, setup wizard and common errors |
| [User guide (Chinese)](docs/user-guide.md) | Installation, frequently asked questions and performance tips |
| [Theme guide (Chinese)](Nimbus/README.md) | Configuration, page templates, shortcodes, custom fields and skin customization |
| [Plugin guide (Chinese)](NimbusKit/README.md) | Features, installation, email / GitHub sign-in / IP geolocation settings, API, data and uninstalling |
| [Changelog](Nimbus/CHANGELOG.md) | Changes by version |
| [Roadmap (Chinese)](docs/ROADMAP.md) | Completed and planned features |

## Repository structure

```text
Nimbus/       Theme → usr/themes/Nimbus
NimbusKit/    Companion plugin → usr/plugins/NimbusKit
docs/         User guide, design docs, roadmap and screenshots
tests/        Local test site, unit tests, smoke tests and screenshot scripts
```

## Contributing

The theme uses native ES modules and CSS cascade layers. Edit a file and refresh to see the result. Before submitting changes, run:

```bash
bash tests/lint.sh && bash tests/lint-php74.sh && php tests/run.php && node --test tests/js/*.test.mjs && bash tests/smoke.sh
php tests/cuteen-compat.php
```

After changing CSS source files, run `php tests/build-css.php` to regenerate the combined stylesheet. See [Theme guide · Development and testing (Chinese)](Nimbus/README.md#开发与测试) for more details. Issues and pull requests are welcome.

## Acknowledgements

- The design was inspired by the Cuteen theme; Nimbus's code is independently implemented.
- Third-party libraries distributed with the theme include Prism, KaTeX, Mermaid and Noto Serif SC. See `Nimbus/assets/vendor/*/LICENSE` and `Nimbus/assets/fonts/LICENSE.txt` for their licenses.
- The IP geolocation data format comes from [ip2region](https://github.com/lionsoul2014/ip2region); download the data files separately.
- Landscape photos in the screenshots come from Bing daily wallpapers and Unsplash and are used for demonstration.

## License

[MIT](LICENSE) © 2026 Nimbus Contributors
