<?php
namespace Nimbus;

final class View
{
    public static function e(?string $s): string
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}
