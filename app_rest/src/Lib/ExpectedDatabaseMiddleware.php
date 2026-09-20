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
    private const int PRECONDITION_FAILED = 412;

    private ConnectedDatabase $_connected;

    public function __construct(?string $databaseUrl = null)
    {
        $this->_connected = new ConnectedDatabase($databaseUrl);
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
        return $this->_connected->label();
    }
}
