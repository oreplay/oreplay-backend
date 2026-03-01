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
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjoiWVRrMlptRTFNVFU0TWprelltUXlPR1F5WldGbFlqRTBaak5rWldRek5ETTRaR1l6WVdOak5ERmlZek0yTkRRNU1UQTNOVEF3TXpaak5tWXhNVE5rT0UyZGVPRGRxWjg0cG1nYmpFT09OVEU4a0xqQzY3NEJnNmsrUldwUnFTaUtVVVErb0lzWms4bFFRTFc3REJrZ1lOS1hibnVwdXdGZkpIbEJYdFo3bWlUU1lqaWx1RlVGajc3WjZvWWkzY1A1TG5YdFwvZmFwNGw1UUVaXC9rdjA2bmJtcDdnMkl4SUE3Tzlud1YrcEIrSWdnckFEbkRLOEc3UnNEUGZzRTZ1NFVxZUdwczBOWkF3MzQxU2pnc0R3Q1p0Q3hET2RzREErTzd5eXNCXC92R3l1SlNId1lhM3grcThjbTFVd3NwblN1UmpXWmN3eHJWdnBLNWhsa2orNjRkMTliSDVmZ1grdzhXTlA1aDkyQ3lBMFBBSUdUQ2U0S1FEVHJcL05MQ29ReXpuNlNpb0ZjeFdLUmdZR2l5d3ZxazFMMDVtRUljTng2dkFTV1NHU0FoeTl4eGJuZzU0THMrWE9XNWIzWGVMZWFpSlwvIiwiaWF0IjoxNzcyMzU5MjI0fQ.4WwXp6ypkjOqOEQkouWCGAeeIIVKkqZys6eMsv9XuVM';
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
        $this->assertStringStartsWith('$2y$10$QPW5', $userDb->password);
    }
}
