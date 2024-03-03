<?php

declare(strict_types = 1);

namespace App\Test\TestCase\Controller;

use App\Controller\ApiController;

class UsersControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        'app.Users'
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/users/';
    }

    public function testAddNew_InputData()
    {
        $data = [
            'email'=> 'test@example.com',
            'first_name'=> 'Test',
            'last_name'=> 'Last',
            'password'=> 'passpass'
        ];

        $this->post($this->_getEndpoint(), $data);

        $this->assertResponseOk($this->_getBodyAsString());
        $return = json_decode($this->_getBodyAsString(), true)['data'];

        $this->assertEquals($data['email'], $return['email']);
        $this->assertEquals($data['first_name'], $return['first_name']);
        $this->assertEquals($data['last_name'], $return['last_name']);
        $this->assertArrayNotHasKey('password', $return);
    }
}
