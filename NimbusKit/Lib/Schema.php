<?php
namespace TypechoPlugin\NimbusKit\Lib;

/**
 * 建表 / 删表语句（mysql / sqlite / pgsql）。纯函数，便于测试。
 */
final class Schema
{
    const TABLES = ['nimbus_stats', 'nimbus_like_log', 'nimbus_moments', 'nimbus_links', 'nimbus_albums', 'nimbus_bookmarks', 'nimbus_projects', 'nimbus_daily'];

    /** Typecho 适配器名（Mysqli、Pdo_Mysql、SQLite、Pdo_SQLite、Pgsql、Pdo_Pgsql）归一。 */
    public static function normalizeAdapter(string $adapter): string
    {
        $a = strtolower($adapter);
        if (strpos($a, 'sqlite') !== false) return 'sqlite';
        if (strpos($a, 'pgsql') !== false || strpos($a, 'postgres') !== false) return 'pgsql';
        return 'mysql';
    }

    public static function statements(string $adapter, string $prefix): array
    {
        $p = $prefix;
        switch (self::normalizeAdapter($adapter)) {
            case 'sqlite':
                $id = 'INTEGER PRIMARY KEY AUTOINCREMENT';
                $tail = '';
                $text = 'TEXT';
                break;
            case 'pgsql':
                $id = 'SERIAL PRIMARY KEY';
                $tail = '';
                $text = 'TEXT';
                break;
            default:
                $id = 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
                $tail = ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
                $text = 'MEDIUMTEXT';
        }
        return [
            "CREATE TABLE IF NOT EXISTS {$p}nimbus_stats (type VARCHAR(10) NOT NULL DEFAULT 'post', cid INT NOT NULL, views INT NOT NULL DEFAULT 0, likes INT NOT NULL DEFAULT 0, PRIMARY KEY (type, cid)){$tail}",
            "CREATE TABLE IF NOT EXISTS {$p}nimbus_like_log (id {$id}, type VARCHAR(10) NOT NULL DEFAULT 'post', cid INT NOT NULL, visitor CHAR(40) NOT NULL, created INT NOT NULL DEFAULT 0){$tail}",
            "CREATE UNIQUE INDEX IF NOT EXISTS {$p}nimbus_like_uniq ON {$p}nimbus_like_log (type, cid, visitor)",
            "CREATE TABLE IF NOT EXISTS {$p}nimbus_moments (id {$id}, content {$text}, images {$text}, created INT NOT NULL DEFAULT 0, status VARCHAR(10) NOT NULL DEFAULT 'publish'){$tail}",
            "CREATE TABLE IF NOT EXISTS {$p}nimbus_links (id {$id}, name VARCHAR(100) NOT NULL DEFAULT '', url VARCHAR(500) NOT NULL DEFAULT '', avatar VARCHAR(500) NOT NULL DEFAULT '', description VARCHAR(500) NOT NULL DEFAULT '', sort INT NOT NULL DEFAULT 0, status VARCHAR(10) NOT NULL DEFAULT 'publish', created INT NOT NULL DEFAULT 0){$tail}",
            "CREATE TABLE IF NOT EXISTS {$p}nimbus_albums (id {$id}, title VARCHAR(200) NOT NULL DEFAULT '', cover VARCHAR(500) NOT NULL DEFAULT '', description {$text}, photos {$text}, sort INT NOT NULL DEFAULT 0, created INT NOT NULL DEFAULT 0){$tail}",
            "CREATE TABLE IF NOT EXISTS {$p}nimbus_bookmarks (id {$id}, group_name VARCHAR(100) NOT NULL DEFAULT '', name VARCHAR(100) NOT NULL DEFAULT '', url VARCHAR(500) NOT NULL DEFAULT '', icon VARCHAR(500) NOT NULL DEFAULT '', description VARCHAR(500) NOT NULL DEFAULT '', sort INT NOT NULL DEFAULT 0, status VARCHAR(10) NOT NULL DEFAULT 'publish', created INT NOT NULL DEFAULT 0){$tail}",
            "CREATE TABLE IF NOT EXISTS {$p}nimbus_projects (id {$id}, name VARCHAR(200) NOT NULL DEFAULT '', description {$text}, cover VARCHAR(500) NOT NULL DEFAULT '', url VARCHAR(500) NOT NULL DEFAULT '', repo VARCHAR(500) NOT NULL DEFAULT '', tags VARCHAR(500) NOT NULL DEFAULT '', sort INT NOT NULL DEFAULT 0, status VARCHAR(10) NOT NULL DEFAULT 'publish', created INT NOT NULL DEFAULT 0){$tail}",
            "CREATE TABLE IF NOT EXISTS {$p}nimbus_daily (day INT NOT NULL PRIMARY KEY, views INT NOT NULL DEFAULT 0){$tail}",
        ];
    }

    /**
     * MySQL 5.7 不支持 CREATE INDEX IF NOT EXISTS；mysql 下改为在建表语句里声明唯一键。
     */
    public static function forAdapter(string $adapter, string $prefix): array
    {
        $s = self::statements($adapter, $prefix);
        if (self::normalizeAdapter($adapter) === 'mysql') {
            $s[1] = str_replace('created INT NOT NULL DEFAULT 0)', "created INT NOT NULL DEFAULT 0, UNIQUE KEY {$prefix}nimbus_like_uniq (type, cid, visitor))", $s[1]);
            unset($s[2]);
            $s = array_values($s);
        }
        return $s;
    }

    public static function dropStatements(string $prefix): array
    {
        $out = [];
        foreach (self::TABLES as $t) $out[] = "DROP TABLE IF EXISTS {$prefix}{$t}";
        return $out;
    }
}
