<?php

declare(strict_types = 1);

namespace RadioRelay\Test\TestCase\Lib\Cpi;

use Cake\Http\Exception\BadRequestException;
use Cake\TestSuite\TestCase;
use RadioRelay\Lib\Cpi\Consts\PunchType;
use RadioRelay\Lib\Cpi\PayloadParser;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Test\Fixture\TokensFixture;

class PayloadParserTest extends TestCase
{
    protected array $fixtures = [
    ];

    public function testGetEventId()
    {
        $eventId = Stage::FIRST_STAGE;
        $secret = Event::FIRST_EVENT . TokensFixture::FIRST_TOKEN;
        $helper = new PayloadParser(['data' => [$eventId, $secret]]);
        $this->assertEquals(Event::FIRST_EVENT, $helper->getEventId());
        //
        $secret = TokensFixture::FIRST_TOKEN . Event::FIRST_EVENT;
        $helper = new PayloadParser(['data' => [$eventId, $secret]]);
        $this->assertEquals(Event::FIRST_EVENT, $helper->getEventId());
        //
        $secret = Event::FIRST_EVENT;
        $helper = new PayloadParser(['data' => [$eventId, $secret]]);
        $exception = false;
        try {
            $helper->getSecret();
        } catch (BadRequestException $e) {
            $exception = $e->getMessage();
        }
        $this->assertEquals('Use the secret and event token together as password', $exception);
    }

    public function testGetSecret()
    {
        $eventId = Stage::FIRST_STAGE;
        $secret = Event::FIRST_EVENT . TokensFixture::FIRST_TOKEN;
        $helper = new PayloadParser(['data' => [$eventId, $secret]]);
        $this->assertEquals(TokensFixture::FIRST_TOKEN, $helper->getSecret());
        //
        $secret = TokensFixture::FIRST_TOKEN . Event::FIRST_EVENT;
        $helper = new PayloadParser(['data' => [$eventId, $secret]]);
        $this->assertEquals(TokensFixture::FIRST_TOKEN, $helper->getSecret());
        //
        $secret = TokensFixture::FIRST_TOKEN;
        $helper = new PayloadParser(['data' => [$eventId, $secret]]);
        $exception = false;
        try {
            $helper->getSecret();
        } catch (BadRequestException $e) {
            $exception = $e->getMessage();
        }
        $this->assertEquals('Use the secret and event token together as password', $exception);
    }

    public function testGetStageId()
    {
        $stageId = Stage::FIRST_STAGE;
        $secret = TokensFixture::FIRST_ID . Event::FIRST_EVENT;
        $helper = new PayloadParser(['data' => [$stageId, $secret]]);
        $this->assertEquals($stageId, $helper->getStageId());
    }

    public function testGetReadingTime()
    {
        $helper = new PayloadParser(['data' => ['fake_event', 'fake_secret']]);

        $punch = [
            'date' => '2025-03-08',
            'raw' => '02d30d80160f85d41b01013c1e7400019db903',
            'reading' => '2025-03-09 05:58:26',
            'sicard' => '2009933',
            'station' => '31',
            'time' => '00:12:50',
            'battery' => '9',
            'type' => PunchType::SI_CARD
        ];
        $timezone = '-04:00';
        $res = $helper->getReadingTime($punch, $timezone);
        $this->assertEquals('2025-03-08T00:12:50-04:00', $res->toIso8601String());

    }
}
