<?php

declare(strict_types = 1);

namespace App\Lib;

use Cake\Http\Exception\HttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ExpectedDatabaseMiddleware implements MiddlewareInterface
{
    public const string HEADER = 'X-Expect-Db';
    public const string LOCAL = 'local';
    public const string REMOTE = 'remote';
    private const array LOCAL_HOSTS = ['mysql', 'localhost', '127.0.0.1', 'host.docker.internal'];
    private const int PRECONDITION_FAILED = 412;

    private ?string $_databaseUrl;

    public function __construct(?string $databaseUrl = null)
    {
        $this->_databaseUrl = $databaseUrl ?? env('DATABASE_URL');
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->_asksForAnotherDatabase($request)) {
            throw new HttpException('Connected database is ' . $this->_connectedLabel(),
                self::PRECONDITION_FAILED);
        }
        return $handler->handle($request);
    }

    private function _asksForAnotherDatabase(ServerRequestInterface $request): bool
    {
        $expected = trim($request->getHeaderLine(self::HEADER));
        return $expected !== '' && $expected !== $this->_connectedLabel();
    }

    private function _connectedLabel(): string
    {
        return $this->_isConnectedToALocalHost() ? self::LOCAL : self::REMOTE;
    }

    private function _isConnectedToALocalHost(): bool
    {
        return in_array($this->_host(), self::LOCAL_HOSTS, true);
    }

    private function _host(): string
    {
        return (string)parse_url((string)$this->_databaseUrl, PHP_URL_HOST);
    }
}
