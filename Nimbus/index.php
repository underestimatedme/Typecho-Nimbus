<?php
/**
 * Nimbus — 开源免费的 Typecho 主题
 *
 * @package Nimbus
 * @author Nimbus Contributors
 * @version 1.0.0
 * @link https://github.com/underestimatedme/Nimbus
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
?><!DOCTYPE html>
<html lang="zh-CN" data-theme="sky">
<head>
<meta charset="utf-8">
<title><?php $this->options->title(); ?></title>
</head>
<body data-page="index">
<?php while ($this->next()): ?>
<h2><a href="<?php $this->permalink(); ?>"><?php $this->title(); ?></a></h2>
<?php endwhile; ?>
</body>
</html>
