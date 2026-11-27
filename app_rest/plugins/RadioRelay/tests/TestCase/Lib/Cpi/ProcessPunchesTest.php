<?php

declare(strict_types = 1);

namespace RadioRelay\Test\TestCase\Lib\Cpi;

use Cake\TestSuite\TestCase;
use RadioRelay\Lib\Cpi\Consts\PunchType;
use RadioRelay\Lib\Cpi\PayloadParser;
use RadioRelay\Lib\Cpi\ProcessPunches;
use Results\Lib\Publish\ChannelPublisher;
use Results\Lib\Publish\ControlChangedPublisher;
use Results\Lib\Publish\WatchedControls;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Model\Table\RunnersTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ControlTypesFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\FederationsFixture;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\TokensFixture;

class ProcessPunchesTest extends TestCase
{
    private const string KNOWN_CARD = '2009933';
    private const string UNKNOWN_CARD = '1';

    protected array $fixtures = [
        FederationsFixture::LOAD,
        EventsFixture::LOAD,
        ControlTypesFixture::LOAD,
        StagesFixture::LOAD,
        TokensFixture::LOAD,
        ClassesFixture::LOAD,
        RunnersFixture::LOAD,
        RunnerResultsFixture::LOAD,
    ];

    public function setUp(): void
    {
        parent::setUp();
        (new WatchedControls())->watchAll(Stage::FIRST_STAGE, ['31', '45']);
    }

    private function _spyChannel(): ChannelPublisher
    {
        return new class implements ChannelPublisher {
            public array $channels = [];
            public array $payloads = [];

            public function publish(string $channel, array $payload): void
            {
                $this->channels[] = $channel;
                $this->payloads[] = $payload;
            }
        };
    }

    private function _punch(string $siCard, string $station, string $time): array
    {
        return [
            'date' => '2025-03-08',
            'raw' => '02d30d80160f85d41b01013c1e7400019db903',
            'reading' => '2025-03-08 05:58:26',
            'sicard' => $siCard,
            'station' => $station,
            'time' => $time,
            'battery' => '9',
            'type' => PunchType::SI_CARD,
        ];
    }

    private function _process(array $punches, ?ChannelPublisher $channel): array
    {
        $payload = [
            'order' => 'ProcessPunches',
            'data' => [Stage::FIRST_STAGE, Event::FIRST_EVENT . TokensFixture::FIRST_TOKEN, '+01:00'],
            'punches' => $punches,
        ];
        $processor = (new ProcessPunches(new PayloadParser($payload)))->setRunnersTable(RunnersTable::load());
        if ($channel) {
            $processor->setControlChangedPublisher(new ControlChangedPublisher($channel, new WatchedControls()));
        }
        return $processor->process();
    }

    public function testSignalsEachStationOncePerBatch()
    {
        $channel = $this->_spyChannel();

        $this->_process([
            $this->_punch(self::KNOWN_CARD, '31', '12:50'),
            $this->_punch(self::KNOWN_CARD, '31', '12:55'),
            $this->_punch(self::KNOWN_CARD, '45', '13:00'),
        ], $channel);

        $stage = 'stage/' . Stage::FIRST_STAGE . '/control/';
        $this->assertSame([$stage . '31', $stage . '45'], $channel->channels);
        $this->assertSame(
            ['station' => '31', 'source' => ControlChangedPublisher::SOURCE_CPI, 'uploadId' => null],
            $channel->payloads[0]
        );
    }

    public function testSignalsNothingForAPunchThatWasNotStored()
    {
        $this->_process([$this->_punch(self::KNOWN_CARD, '31', '12:50')], null);
        $channel = $this->_spyChannel();

        $this->_process([
            $this->_punch(self::UNKNOWN_CARD, '45', '12:50'),
            $this->_punch(self::KNOWN_CARD, '31', '12:50'),
        ], $channel);

        $this->assertSame([], $channel->channels);
    }

    public function testProcessesPunchesWithoutAPublisher()
    {
        $answer = $this->_process([$this->_punch(self::KNOWN_CARD, '31', '12:50')], null);

        $this->assertSame(['OK', '1', '1'], $answer);
    }
}
