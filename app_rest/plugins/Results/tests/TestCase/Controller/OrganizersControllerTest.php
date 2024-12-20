<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Model\Entity\Organizer;
use Results\Test\Fixture\OrganizersFixture;

class OrganizersControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        OrganizersFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/organizers/';
    }

    public function testGetList()
    {
        $this->get($this->_getEndpoint());

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertNotEmpty($bodyDecoded);
        $expected = [
            [
                'id' => Organizer::ID,
                'name' => Organizer::NAME,
                'country' => 'ES',
                'region' => 'ES-VC',
            ],
        ];
        
        $this->assertEquals($expected, $bodyDecoded['data']);
    }
}
