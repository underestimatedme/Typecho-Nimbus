<?php
/**
 * English language pack. Keys are the original Chinese strings in the source.
 * 'ui' is used by PHP templates; 'js' is also sent to the browser in NimbusConfig.i18n.
 * Placeholders such as {n} must be kept.
 */
return [
'ui' => [
    '发布闪念' => 'Publish a moment',
    // 通用
    '首页' => 'Home', '搜索' => 'Search', '搜索 {q}' => 'Search: {q}', '搜索文章…' => 'Search posts…', '返回首页' => 'Back to home',
    '返回' => 'Back', '关闭' => 'Close', '菜单' => 'Menu', '打开菜单' => 'Open menu', '主导航' => 'Main navigation',
    '切换亮暗模式' => 'Toggle light / dark', '亮暗切换' => 'Light / dark', '返回顶部' => 'Back to top', '向下滚动' => 'Scroll down',
    '加载更多' => 'Load more', '浏览方式' => 'Browse mode', '分页' => 'Pages', '滚动' => 'Scroll', '没有更多了' => 'No more', '加载中…' => 'Loading…', '全部' => 'All', '更多' => 'More',
    '文章' => 'Posts', '分类' => 'Categories', '标签' => 'Tags', '作者' => 'Author', '归档' => 'Archives', '其他' => 'Other',
    '共 {n} 篇文章' => '{n} posts', '共 {n} 篇' => '{n} posts', '{n} 篇' => '{n} posts', '这里还没有文章。' => 'No posts here yet.', '还没有文章。' => 'No posts yet.',
    '页面不见了' => 'Page not found', '页面不存在' => 'Page not found', '你访问的地址可能已被移动或删除。试试搜索，或者看看这些文章。' => 'The page you are looking for may have been moved or deleted. Try searching, or have a look at these posts.',
    '随便看看' => 'You might like', '置顶' => 'Pinned', '轮播' => 'carousel', '第 {n} 张' => 'Slide {n}', '上一张' => 'Previous', '下一张' => 'Next',
    '开始阅读' => 'Start reading', '阅读全文' => 'Read more', '原文' => 'Original', '公告' => 'Notice', '未命名' => 'Untitled',
    '本博客完全使用 {theme}构建。' => 'This blog is built entirely with the {theme}.', 'Nimbus 主题' => 'Nimbus theme',
    // 相对时间
    '刚刚' => 'just now', '{n} 分钟前' => '{n} min ago', '{n} 小时前' => '{n} h ago', '{n} 天前' => '{n} days ago',
    // 文章
    '{n} 字' => '{n} words', '{n} 分钟' => '{n} min read', '目录' => 'Contents', '分享' => 'Share', '微博' => 'Weibo', '生成海报' => 'Poster',
    '复制链接' => 'Copy link', '点赞' => 'Like', '打赏' => 'Tip', '请我喝杯咖啡 ☕' => 'Buy me a coffee ☕', '如果这篇文章对你有帮助，欢迎打赏支持。' => 'If this post helped you, consider leaving a tip.',
    '微信' => 'WeChat', '支付宝' => 'Alipay', '{name}收款码' => '{name} payment code', '{name}扫码' => 'Scan with {name}',
    '本文作者' => 'Author', '本文链接' => 'Link', '版权声明' => 'License', '上一篇 / 下一篇' => 'Previous / next post', '上一篇' => 'Previous', '下一篇' => 'Next', '相关文章' => 'Related posts', '继续阅读' => 'Keep reading', '位置' => 'Breadcrumb', '文章操作' => 'Post actions',
    '本文最后更新于 {n} 天前，其中的信息可能已经过时，请谨慎参考。' => 'This post was last updated {n} days ago. Some information may be out of date.',
    '这篇文章受密码保护' => 'This post is password protected', '请输入密码查看内容。' => 'Enter the password to read it.', '密码' => 'Password', '解锁' => 'Unlock',
    // 短代码
    '展开查看' => 'Show details', '链接' => 'Link', 'GitHub 仓库' => 'GitHub repository',
    '以下为评论后可见的内容' => 'Unlocked: visible after commenting', '此处内容需要{link}后可见' => 'This content is visible after you {link}', '（此处内容需要评论后可见，请访问原文）' => '(Hidden content: comment on the original post to see it.)',
    // 评论
    '评论' => 'Comments', '留言' => 'Messages', '{n} 条评论' => '{n} comments', '{n} 条留言' => '{n} messages', '您的评论正在等待审核' => 'Your comment is awaiting moderation.',
    '还没有评论，来说点什么吧。' => 'No comments yet. Be the first!', '还没有留言，来说点什么吧。' => 'No messages yet. Be the first!',
    '发表评论' => 'Leave a comment', '发表留言' => 'Leave a message', '取消回复' => 'Cancel reply', '以 {name} 的身份评论' => 'Commenting as {name}', '退出' => 'Sign out',
    '本站仅允许 GitHub 用户评论，登录后即可发表。' => 'Only GitHub users can comment here. Sign in to join the discussion.', '本站仅允许 GitHub 用户留言，登录后即可发表。' => 'Only GitHub users can leave messages here. Sign in to join in.',
    '使用 GitHub 登录' => 'Sign in with GitHub', 'GitHub 登录' => 'GitHub sign-in', '或直接填写昵称与邮箱' => 'or fill in your name and email',
    '昵称' => 'Name', '怎么称呼你' => 'Your name', '邮箱' => 'Email', '不会公开' => 'Never shown publicly', '网址' => 'Website',
    '说点什么…（支持 Markdown）' => 'Say something… (Markdown supported)', '表情' => 'Emoji', '预览' => 'Preview', 'Ctrl + Enter 快速提交' => 'Ctrl + Enter to send', '发表' => 'Send',
    '评论已关闭' => 'Comments are closed', '博主' => 'Author', 'IP 属地' => 'IP location', '回复' => 'Reply', 'QQ 浏览器' => 'QQ Browser',
    '评论成功' => 'Comment posted', '评论已提交，正在等待审核' => 'Comment submitted and awaiting moderation',
    '请先使用 GitHub 登录后再发表评论' => 'Please sign in with GitHub before commenting.',
    '登录请求已过期或无效，请返回重新点击「GitHub 登录」。' => 'The sign-in request has expired or is invalid. Go back and click "GitHub sign-in" again.',
    'GitHub 没有返回授权码。' => 'GitHub did not return an authorization code.', '无法向 GitHub 换取登录凭据，请稍后再试。' => 'Could not get sign-in credentials from GitHub. Please try again later.',
    '读取 GitHub 账号信息失败，请稍后再试。' => 'Could not read your GitHub profile. Please try again later.', '关键词需为 2 到 40 个字' => 'Keywords must be 2 to 40 characters',
    // 页面
    '时间线' => 'Timeline', '时光河流 · 共 {n} 篇文章' => 'River of time · {n} posts', '向前' => 'Earlier', '向后' => 'Later', '文章时间线，可左右滚动' => 'Post timeline, scroll horizontally',
    '左右滑动、拖拽或使用方向键浏览 · 点击卡片阅读' => 'Swipe, drag or use the arrow keys · click a card to read',
    '留言板' => 'Guestbook', '说说' => 'Moments', '共 {n} 条' => '{n} in total', '记录生活的碎片' => 'Fragments of everyday life', '说说功能需要启用配套插件 NimbusKit。' => 'Moments require the companion plugin NimbusKit.',
    '还没有说说，去后台「管理 → Nimbus」发布第一条吧。' => 'No moments yet. Post one under "Manage → Nimbus" in the dashboard.',
    '收藏夹' => 'Bookmarks', '{n} 个站点 · {g} 个分组' => '{n} sites · {g} groups', '收集常用的好用站点和工具' => 'Handy sites and tools', '收藏夹功能需要启用配套插件 NimbusKit。' => 'Bookmarks require the companion plugin NimbusKit.',
    '还没有收藏，去后台「管理 → Nimbus → 收藏夹」添加吧。' => 'No bookmarks yet. Add some under "Manage → Nimbus → Bookmarks".', '过滤站点…' => 'Filter sites…', '没有匹配的站点。' => 'No matching sites.', '未分组' => 'Ungrouped',
    '音乐' => 'Music', '还没有开启音乐播放器。请在「主题设置 → 媒体」中开启并填写歌单。' => 'The music player is off. Turn it on and add a playlist under "Theme settings → Media".',
    '播放进度' => 'Progress', '上一首' => 'Previous track', '播放 / 暂停' => 'Play / pause', '下一首' => 'Next track', '音量' => 'Volume', '歌单' => 'Playlist',
    '相册' => 'Albums', '全部相册' => 'All albums', '{n} 张照片' => '{n} photos', '{n} 张' => '{n} photos', '这个相册还没有照片。' => 'This album has no photos yet.', '{n} 个相册' => '{n} albums',
    '用照片记录时光' => 'Moments kept in photos', '相册功能需要启用配套插件 NimbusKit。' => 'Albums require the companion plugin NimbusKit.', '还没有相册，去后台「管理 → Nimbus」新建一个吧。' => 'No albums yet. Create one under "Manage → Nimbus".',
    '项目' => 'Projects', '{n} 个作品' => '{n} projects', '从零到一，用代码构建的每一份作品' => 'Things built from scratch, one commit at a time', '项目展示需要启用配套插件 NimbusKit。' => 'Projects require the companion plugin NimbusKit.',
    '还没有项目，去后台「管理 → Nimbus → 项目」添加吧。' => 'No projects yet. Add some under "Manage → Nimbus → Projects".', '访问' => 'Visit', '源码' => 'Source',
    '友链' => 'Friends', '共 {n} 位朋友' => '{n} friends', '还没有友链。在本页编辑界面的「友链数据」字段填入 JSON 即可。' => 'No links yet. Fill in the "Links data" field (JSON) when editing this page.',
    // 统计
    '统计' => 'Stats', '数字背后，是每一次写作与相遇' => 'Behind every number, a post written and a reader met', '篇' => 'posts', '条' => 'comments', '总字数' => 'Words', '字' => 'words', '运行' => 'Running', '天' => 'days',
    '总浏览' => 'Views', '次' => 'views', '获赞' => 'Likes', '个' => '', '近 30 天浏览量' => 'Views in the last 30 days', '{n} 次' => '{n} views', ' 次' => ' views', '近 12 个月' => 'Last 12 months',
    '写作热力图' => 'Writing heatmap', '近一年 {n} 篇' => '{n} posts in the past year', '少' => 'Less', '多' => 'More', '分类分布' => 'Categories', '篇文章' => 'posts', '暂无数据' => 'No data yet',
    '文章总数已去重；一篇文章可属于多个分类，占比按分类归属次数计算。' => 'The total counts each post once. Posts can belong to multiple categories; shares are based on category assignments.',
    '热门文章' => 'Popular posts', '查看全部' => 'View all', '前 {n} 篇 · 按浏览量' => 'Top {n} · by views', '按浏览量' => 'by views', '常用标签' => 'Top tags', '启用配套插件 NimbusKit 后可显示浏览量趋势与热门文章。' => 'Enable the companion plugin NimbusKit to see view trends and popular posts.',
    '折线图' => 'Line chart', '柱状图' => 'Bar chart', '环形图' => 'Donut chart', '发文热力图' => 'Posting heatmap', '：' => ': ', '{date}：{n} 篇' => '{date}: {n} posts',
    // 日期
    '{m}月' => '{m}', '{m} 月' => 'Month {m}', '{y} 年 {m} 月' => '{y}-{m}', '一' => 'M', '二' => 'T', '三' => 'W', '四' => 'T', '五' => 'F', '六' => 'S', '日' => 'S',
    // 杂志风首页
    '欢迎来到 {site}' => 'Welcome to {site}', '每日必应' => 'Bing daily', '随便看看' => 'Surprise me', '条评论' => 'comments', '天在写' => 'days writing', '个标签' => 'tags',
    '最新文章' => 'Latest stories', '最新' => 'New', '精选' => 'Featured', '关于我' => 'About me', '写作节奏' => 'Writing pulse', '篇 / 近一年' => 'posts / past year',
    '共 {n} 篇 · {c} 条评论' => '{n} posts · {c} comments', '最新说说' => 'Latest moment', '正在播放' => 'Now playing', '听一首歌' => 'Play a song', '此刻' => 'Right now',
    '记录、思考，与世界温柔相处' => 'Writing, thinking, and being gentle with the world', '慢慢写，慢慢读' => 'Write slowly, read slowly', '全部归档' => 'All archives',
    // 教程
    '系列教程' => 'Series', '{n} 章' => '{n} chapters', '约 {n} 分钟' => 'about {n} min', '更新于 {d}' => 'Updated {d}', '还没开始' => 'Not started', '开始学习' => 'Start', '章' => 'chapters', '分钟' => 'min',
    '跟着教程，一步一步来' => 'Learn step by step', '全部教程' => 'All series', '第 {i} / {n} 章' => 'Chapter {i} / {n}', '查看教程目录' => 'View all chapters', '上一章' => 'Previous chapter', '下一章' => 'Next chapter',
    '{n} 个教程' => '{n} series', '还没有教程。在「主题设置 → 首页 → 教程分类」中填写分类缩略名即可。' => 'No series yet. Add category slugs under "Theme settings → Home → Tutorial categories".',
    // 时间线
    '教程' => 'Series', '开始连载' => 'Series started', '{n} 项' => '{n} items', '新项目' => 'New project', '筛选' => 'Filter', '{n} 个项目' => '{n} projects', '第 {n} 章' => 'Chapter {n}', '最新：{t}' => 'Latest: {t}',
    // 侧栏
    '站点信息' => 'Site info', '运行天数' => 'Days online', '{n} 天' => '{n} days', '文章数' => 'Posts', '评论数' => 'Comments', '分类数' => 'Categories', '标签数' => 'Tags', '最后更新' => 'Last update', '最新评论' => 'Recent comments',
    // IP 属地（数据为中文，常见地区给出英文）
    '本地' => 'Local', '中国' => 'China', '北京' => 'Beijing', '天津' => 'Tianjin', '上海' => 'Shanghai', '重庆' => 'Chongqing', '河北' => 'Hebei', '山西' => 'Shanxi', '辽宁' => 'Liaoning', '吉林' => 'Jilin',
    '黑龙江' => 'Heilongjiang', '江苏' => 'Jiangsu', '浙江' => 'Zhejiang', '安徽' => 'Anhui', '福建' => 'Fujian', '江西' => 'Jiangxi', '山东' => 'Shandong', '河南' => 'Henan', '湖北' => 'Hubei', '湖南' => 'Hunan',
    '广东' => 'Guangdong', '海南' => 'Hainan', '四川' => 'Sichuan', '贵州' => 'Guizhou', '云南' => 'Yunnan', '陕西' => 'Shaanxi', '甘肃' => 'Gansu', '青海' => 'Qinghai', '台湾' => 'Taiwan', '内蒙古' => 'Inner Mongolia',
    '广西' => 'Guangxi', '西藏' => 'Tibet', '宁夏' => 'Ningxia', '新疆' => 'Xinjiang', '香港' => 'Hong Kong', '澳门' => 'Macao',
    '美国' => 'United States', '日本' => 'Japan', '韩国' => 'South Korea', '新加坡' => 'Singapore', '英国' => 'United Kingdom', '德国' => 'Germany', '法国' => 'France', '加拿大' => 'Canada', '澳大利亚' => 'Australia',
    '俄罗斯' => 'Russia', '印度' => 'India', '马来西亚' => 'Malaysia', '泰国' => 'Thailand', '越南' => 'Vietnam', '荷兰' => 'Netherlands', '意大利' => 'Italy', '西班牙' => 'Spain', '巴西' => 'Brazil', '新西兰' => 'New Zealand',
    '阿联酋' => 'United Arab Emirates', '菲律宾' => 'Philippines', '印度尼西亚' => 'Indonesia', '瑞士' => 'Switzerland', '瑞典' => 'Sweden', '爱尔兰' => 'Ireland', '芬兰' => 'Finland',
],
'js' => [
    '复制' => 'Copy', '还没开始' => 'Not started', '已学完 · 共 {n} 章' => 'Completed · {n} chapters', '已学 {d} / {n} 章' => '{d} / {n} chapters read', '重新学习' => 'Start over', '继续第 {i} 章' => 'Continue chapter {i}', '{y} 年 {m} 月 {d} 日' => '{y}-{m}-{d}', '{n} 篇' => '{n} posts', '查看当天归档' => 'Day archive', '加载失败，请重试' => 'Could not load. Please try again.', '已复制' => 'Copied', '复制失败，请手动选择' => 'Copy failed. Please select the text manually.', '展开全部 · {n} 行' => 'Expand · {n} lines', '收起' => 'Collapse',
    '点赞失败' => 'Could not like', '点赞失败，请稍后再试' => 'Could not like. Please try again later.', '更多' => 'More', '加载中…' => 'Loading…', '没有更多了' => 'No more',
    '表情加载失败' => 'Could not load emoji', '回复 {name}' => 'Reply to {name}', '评论成功' => 'Comment posted', '提交失败' => 'Submission failed',
    '评论没有提交成功，请刷新页面后重试' => 'The comment was not posted. Refresh the page and try again.', '，隐藏内容已解锁' => '; hidden content unlocked', '提交失败，请重试' => 'Submission failed. Please try again.',
    '关闭' => 'Close', '上一张' => 'Previous', '下一张' => 'Next', '未命名' => 'Untitled', '长按或扫描二维码' => 'Scan or long-press the QR code', '阅读全文 →' => 'to read the full post →',
    '分享海报' => 'Share poster', '正在生成海报…' => 'Creating poster…', '下载图片' => 'Download image', '手机上可长按图片保存或分享' => 'On mobile, long-press the image to save or share', '海报生成失败' => 'Could not create the poster',
    '没有找到相关文章，回车使用站内搜索' => 'No matches. Press Enter for a full search.', '歌词加载失败' => 'Could not load lyrics', '纯音乐，请欣赏' => 'Instrumental — enjoy', '未知艺术家' => 'Unknown artist',
    '链接已复制' => 'Link copied', '复制失败' => 'Copy failed',
],
];
