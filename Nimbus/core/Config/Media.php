<?php
namespace Nimbus\Config;

final class Media
{
    public static function title(): string { return '媒体'; }

    public static function items(): array
    {
        return [
            ['name' => 'MusicEnable', 'type' => 'radio', 'label' => '音乐播放器', 'description' => '开启后页面左下角出现悬浮播放器，无刷新切换页面时不中断播放。音乐页：新建独立页面，模板选「音乐」。', 'default' => '0', 'options' => ['1' => '开启', '0' => '关闭']],
            ['name' => 'MusicSource', 'type' => 'radio', 'label' => '歌单来源', 'description' => '自定义：在下方逐行填写歌曲；接口：使用 Meting 兼容接口读取音乐平台歌单（需自行提供接口地址）。', 'default' => 'list', 'options' => ['list' => '自定义列表', 'meting' => 'Meting 接口']],
            ['name' => 'MusicList', 'type' => 'textarea', 'label' => '自定义歌曲', 'description' => '每行一首：歌名|歌手|音频URL|封面URL|歌词URL（封面、歌词可省略）。歌词为 LRC 格式；跨域地址需对方允许 CORS 才能读取歌词。', 'default' => ''],
            ['name' => 'MusicMetingApi', 'type' => 'text', 'label' => 'Meting 接口地址', 'description' => '用 :server、:id 作为占位符，例如 https://api.example.com/meting/?server=:server&type=playlist&id=:id', 'default' => ''],
            ['name' => 'MusicMetingServer', 'type' => 'radio', 'label' => '音乐平台', 'description' => '', 'default' => 'netease', 'options' => ['netease' => '网易云音乐', 'tencent' => 'QQ 音乐', 'kugou' => '酷狗', 'kuwo' => '酷我']],
            ['name' => 'MusicMetingId', 'type' => 'text', 'label' => '歌单 ID', 'description' => '平台歌单页面地址里的数字 ID。', 'default' => ''],
            ['name' => 'MusicAutoplay', 'type' => 'radio', 'label' => '自动播放', 'description' => '浏览器通常会阻止未经点击的自动播放，开启后仅在允许时生效。', 'default' => '0', 'options' => ['1' => '尝试自动播放', '0' => '不自动播放']],
            ['name' => 'Live2dEnable', 'type' => 'radio', 'label' => 'Live2D 看板娘', 'description' => '从 CDN 加载第三方挂件，Nimbus 不打包任何相关文件。请注意：live2d-widget 为 GPL-3 许可，Live2D Cubism SDK 为 Live2D 公司专有许可，示例模型版权归原作者、仅限非商业用途。商业站点请自备授权模型。', 'default' => '0', 'options' => ['1' => '开启', '0' => '关闭']],
            ['name' => 'Live2dScript', 'type' => 'text', 'label' => 'Live2D 脚本地址', 'description' => 'live2d-widget 的 autoload.js 地址，可换成自建或其他兼容挂件。', 'default' => 'https://fastly.jsdelivr.net/npm/live2d-widgets@1.0.1/dist/autoload.js'],
            ['name' => 'Live2dMobile', 'type' => 'radio', 'label' => '手机端显示', 'description' => '手机屏幕小、流量贵，默认不加载。', 'default' => '0', 'options' => ['1' => '显示', '0' => '不显示']],
        ];
    }
}
