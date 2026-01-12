<?php

declare(strict_types = 1);

namespace App\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Lib\Emails\EmailBase;
use App\Model\Table\UsersTable;

class ValidateTokensControllerTest extends ApiCommonErrorsTest
{
    protected array $fixtures = [
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/validateTokens/';
    }

    public function testAddNew()
    {
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6Ijg5ZGM4NjM4LWQwODYtNDZlNi04NGM2LTY4MzE2ZTliZGZlNSIsImVtYWlsIjoic2tpcC1zZW5kQGV4YW1wbGUuY29tIiwibGFzdF9uYW1lIjoiTGFzdCIsImZpcnN0X25hbWUiOiJUZXN0IiwiX2MiOiJVc2VyIiwidG9rZW5fdHlwZSI6InZlcmlmeV9hZGRyZXNzIiwiaWF0IjoxNzY4MjA1NTA3fQ.vpZA1evaqBIbDsEuskXC4mJDEXHH-nyYSuGEa2c7x44';
        $this->post($this->_getEndpoint(), ['token' => $token]);

        $this->assertResponseOk($this->_getBodyAsString());
        $return = $this->assertJsonResponseOK()['data'];

        $this->assertEquals(EmailBase::SKIP_SEND_EMAIL_ADDRESS, $return['email']);
        $this->assertEquals('Test', $return['first_name']);
        $this->assertEquals('Last', $return['last_name']);
        $this->assertArrayHasKey('id', $return);
        $this->assertArrayHasKey('created', $return);
        $this->assertArrayHasKey('modified', $return);
        $this->assertArrayNotHasKey('password', $return);
        $userDb = UsersTable::load()->get($return['id']);
        $this->assertEquals(EmailBase::SKIP_SEND_EMAIL_ADDRESS, $userDb['email']);
        $this->assertFalse($userDb->is_admin);
        $this->assertFalse($userDb->is_super);
    }
}
