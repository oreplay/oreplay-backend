<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\Fixture\OauthAccessTokensFixture;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Model\Entity\Event;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\TokensFixture;
use Results\Test\Fixture\FederationsFixture;
use Results\Test\Fixture\StagesFixture;

class EventTokensControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        FederationsFixture::LOAD,
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        OauthAccessTokensFixture::LOAD,
        TokensFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/tokens/';
    }

    public function testAddNew()
    {
        $this->post($this->_getEndpoint(), ['jlkajdsf']);// TODO change

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertArrayHasKey('id', $bodyDecoded['data']);
        $this->assertArrayHasKey('token', $bodyDecoded['data']);
        $this->assertEquals(4, count($bodyDecoded['data']));
    }
}
