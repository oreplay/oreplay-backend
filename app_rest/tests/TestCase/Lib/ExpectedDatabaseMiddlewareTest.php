<?php

declare(strict_types = 1);

namespace App\Test\TestCase\Lib;

use App\Lib\ExpectedDatabaseMiddleware;
use Cake\Http\Exception\HttpException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ExpectedDatabaseMiddlewareTest extends TestCase
{
    private const string LOCAL_URL = 'mysql://root:password@mysql:3306/app_rest';
    private const string REMOTE_URL = 'mysql://admin:secret@o-replay2024.mysql.database.azure.com:3306/app_rest';

    private function _handler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public bool $handled = false;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->handled = true;
                return new Response();
            }
        };
    }

    private function _request(?string $expected): ServerRequest
    {
        $request = new ServerRequest(['url' => '/api/v1/ping/pong']);
        if ($expected === null) {
            return $request;
        }
        return $request->withHeader(ExpectedDatabaseMiddleware::HEADER, $expected);
    }

    public function testProcess_shouldPassRequestsWithoutTheHeader()
    {
        $handler = $this->_handler();

        (new ExpectedDatabaseMiddleware(self::REMOTE_URL))->process($this->_request(null), $handler);

        $this->assertTrue($handler->handled,
            'clients that know nothing about the header must keep working against any database');
    }

    public function testProcess_shouldPassWhenTheLocalDatabaseIsTheExpectedOne()
    {
        $handler = $this->_handler();

        (new ExpectedDatabaseMiddleware(self::LOCAL_URL))->process($this->_request('local'), $handler);

        $this->assertTrue($handler->handled);
    }

    public function testProcess_shouldRejectWhenLocalIsExpectedButTheDatabaseIsRemote()
    {
        $handler = $this->_handler();

        try {
            (new ExpectedDatabaseMiddleware(self::REMOTE_URL))->process($this->_request('local'), $handler);
            $this->fail('a request meant for the local database must not reach a remote one');
        } catch (HttpException $e) {
            $this->assertEquals(412, $e->getCode());
            $this->assertEquals('Connected database is remote', $e->getMessage());
            $this->assertStringNotContainsString('secret', $e->getMessage(),
                'the message is rendered to the client, so it must never carry the credentials');
            $this->assertFalse($handler->handled);
        }
    }

    public function testProcess_shouldRejectWhenRemoteIsExpectedButTheDatabaseIsLocal()
    {
        $handler = $this->_handler();

        try {
            (new ExpectedDatabaseMiddleware(self::LOCAL_URL))->process($this->_request('remote'), $handler);
            $this->fail('the check works in both directions, so a production script cannot hit a dev database');
        } catch (HttpException $e) {
            $this->assertEquals('Connected database is local', $e->getMessage());
        }
    }

    public function testProcess_shouldTreatEveryKnownDevelopmentHostAsLocal()
    {
        foreach (['mysql', 'localhost', '127.0.0.1', 'host.docker.internal'] as $host) {
            $handler = $this->_handler();

            (new ExpectedDatabaseMiddleware('mysql://root:password@' . $host . ':3306/app_rest'))
                ->process($this->_request('local'), $handler);

            $this->assertTrue($handler->handled, $host . ' is a development database host');
        }
    }

    public function testProcess_shouldTreatAnUnreadableUrlAsRemote()
    {
        $handler = $this->_handler();

        try {
            (new ExpectedDatabaseMiddleware(''))->process($this->_request('local'), $handler);
            $this->fail('without a url there is no proof of being local, so the safe answer is remote');
        } catch (HttpException $e) {
            $this->assertEquals('Connected database is remote', $e->getMessage());
        }
    }
}
