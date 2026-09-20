<?php

declare(strict_types = 1);

namespace App\Lib;

class ConnectedDatabase
{
    public const string LOCAL = 'local';
    public const string REMOTE = 'remote';
    private const array LOCAL_HOSTS = ['mysql', 'localhost', '127.0.0.1', 'host.docker.internal'];

    private ?string $_databaseUrl;

    public function __construct(?string $databaseUrl = null)
    {
        $this->_databaseUrl = $databaseUrl ?? env('DATABASE_URL');
    }

    public function label(): string
    {
        return $this->_isALocalHost() ? self::LOCAL : self::REMOTE;
    }

    private function _isALocalHost(): bool
    {
        return in_array($this->_host(), self::LOCAL_HOSTS, true);
    }

    private function _host(): string
    {
        return (string)parse_url((string)$this->_databaseUrl, PHP_URL_HOST);
    }
}
