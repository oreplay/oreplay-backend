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
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6IjFlZTM5MzY2LTMyYzctNDI2My04ZGVlLTAzZDk0MTk2ZTYyYyIsImVtYWlsIjoic2tpcC1zZW5kQGV4YW1wbGUuY29tIiwibGFzdF9uYW1lIjoiTGFzdCIsImZpcnN0X25hbWUiOiJUZXN0IiwiX2MiOiJVc2VyIiwiaGFzaGVkX3Bhc3N3b3JkIjoiJDJ5JDEwJEY1OEYwZXJkSzBCeUI2NWt6ZXd3WmVKbkNRWUY2VHBsQnBSTmlQS1VYOUdoNTY0ZkdXSUZXIiwidG9rZW5fdHlwZSI6InZlcmlmeV9hZGRyZXNzIiwiaWF0IjoxNzcyMjczNTkzfQ.YLAcuMAkuORdje_OmuAkOhP2WBFAH8cKc4rKfVQqDT8';
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
        $this->assertStringStartsWith('$2y$10$F58F', $userDb->password);
    }
}
