<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\Fixture\OauthAccessTokensFixture;
use App\Test\Fixture\UsersFixture;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Model\Entity\Event;
use Results\Model\Entity\Token;
use Results\Model\Table\TokensTable;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\UsersEventsFixture;
use Results\Test\Fixture\TokensFixture;
use Results\Test\Fixture\FederationsFixture;
use Results\Test\Fixture\StagesFixture;

class EventTokensControllerTest extends ApiCommonErrorsTest
{
    protected array $fixtures = [
        FederationsFixture::LOAD,
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        OauthAccessTokensFixture::LOAD,
        TokensFixture::LOAD,
        UsersFixture::LOAD,
        UsersEventsFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/tokens/';
    }

    public function testAddNew()
    {
        $data = ['expires' => '2034-01-06T13:09:01.523+00:00'];
        $this->post($this->_getEndpoint(), $data);

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertArrayHasKey('token', $bodyDecoded['data']);
        $this->assertEquals($data['expires'], $bodyDecoded['data']['expires']);
        $this->assertArrayHasKey('created', $bodyDecoded['data']);
        $this->assertEquals(3, count($bodyDecoded['data']));
    }

    public function testGetList()
    {
        $this->get($this->_getEndpoint());

        $bodyDecoded = $this->assertJsonResponseOK();
        $expected = [
            'data' => [
                [
                    'token' => TokensFixture::FIRST_TOKEN,
                    'expires' => '2036-05-05T10:00:08.000+00:00',
                    'created' => '2024-05-05T10:00:08.000+00:00',
                ]
            ]
        ];
        $this->assertEquals($expected, $bodyDecoded);
    }

    public function testDelete()
    {
        $this->delete($this->_getEndpoint() . TokensFixture::FIRST_TOKEN);

        $this->assertResponseOK();
        /** @var Token $db */
        $db = TokensTable::load()->find()
            ->where(['id' => TokensFixture::FIRST_ID])
            ->withDeleted(true)
            ->firstOrFail();
        $this->assertEquals(TokensFixture::FIRST_TOKEN, $db->token);
        $this->assertTrue($db->expires->isPast());
        $this->assertTrue($db->deleted->isPast());
    }
}
