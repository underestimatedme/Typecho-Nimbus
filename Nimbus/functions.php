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

function themeFields($layout)
{
    \Nimbus\Config\Fields::render($layout);
}

function themeInit($archive)
{
    NimbusBootstrap::init($archive);
}
