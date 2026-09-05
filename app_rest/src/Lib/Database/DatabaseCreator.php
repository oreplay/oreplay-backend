<?php

declare(strict_types = 1);

namespace App\Lib\Database;

use Cake\Database\Driver\Mysql;
use Cake\Database\Exception\MissingConnectionException;
use Cake\Datasource\ConnectionManager;

class DatabaseCreator
{
    private const UNKNOWN_DATABASE_ERROR = '[1049]';
    private const SAFE_DATABASE_NAME = '/^[A-Za-z0-9_]+$/';

    public static function createIfMissing(string $connectionName = 'default'): bool
    {
        $config = ConnectionManager::get($connectionName)->config();
        if (!self::_isCreatable($config)) {
            return false;
        }
        if (!self::_isUnknownDatabase($config)) {
            return false;
        }
        self::_create($config);
        return true;
    }

    private static function _isCreatable(array $config): bool
    {
        if (($config['driver'] ?? null) !== Mysql::class) {
            return false;
        }
        return (bool)preg_match(self::SAFE_DATABASE_NAME, (string)($config['database'] ?? ''));
    }

    private static function _isUnknownDatabase(array $config): bool
    {
        try {
            (new Mysql($config))->connect();
        } catch (MissingConnectionException $e) {
            return str_contains($e->getMessage(), self::UNKNOWN_DATABASE_ERROR);
        }
        return false;
    }

    private static function _create(array $config): void
    {
        $database = $config['database'];
        $config['database'] = null;
        $config['init'][] = "CREATE DATABASE IF NOT EXISTS `$database`";
        (new Mysql($config))->connect();
    }
}
