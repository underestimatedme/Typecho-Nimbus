<?php
/**
 * Nimbus 主题入口。逻辑全部在 core/ 下的 Nimbus\ 命名空间类中。
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

require_once __DIR__ . '/core/Bootstrap.php';

function themeConfig($form)
{
    \Nimbus\Admin::render($form);
}

/** 保存主题设置（修正 Typecho 对从未保存过的多选项一律存为空值的问题）。 */
function themeConfigHandle(array $settings, bool $isInit)
{
    \Nimbus\Admin::save($settings, $isInit);
}

function themeFields($layout)
{
    \Nimbus\Config\Fields::render($layout);
}

function themeInit($archive)
{
    NimbusBootstrap::init($archive);
}
