<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Publish;

use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use Results\Lib\Publish\ChannelPublisher;
use Results\Lib\Publish\ControlChangedPublisher;
use Results\Lib\Publish\WatchedControls;

class ControlChangedPublisherTest extends TestCase
{
    private string $stageId;
    private WatchedControls $watched;

    public function setUp(): void
    {
        parent::setUp();
        $this->stageId = Text::uuid();
        $this->watched = new WatchedControls();
    }

    private function _spyChannel(): ChannelPublisher
    {
        return new class implements ChannelPublisher {
            public array $published = [];

            public function publish(string $channel, array $payload): void
            {
                $this->published[] = [$channel, $payload];
            }
        };
    }

    public function testPublishesEachDistinctWatchedStationOnce()
    {
        $this->watched->watchAll($this->stageId, ['100', '31']);
        $channel = $this->_spyChannel();

        (new ControlChangedPublisher($channel, $this->watched))
            ->publish($this->stageId, ['100', '31', '100', ''], ControlChangedPublisher::SOURCE_CPI);

        $expected = [
            ['stage/' . $this->stageId . '/control/100', ['station' => '100', 'source' => 'cpi', 'uploadId' => null]],
            ['stage/' . $this->stageId . '/control/31', ['station' => '31', 'source' => 'cpi', 'uploadId' => null]],
        ];
        $this->assertSame($expected, $channel->published);
    }

    public function testPublishesNothingToAControlNobodyWatches()
    {
        $this->watched->watch($this->stageId, '100');
        $channel = $this->_spyChannel();

        (new ControlChangedPublisher($channel, $this->watched))
            ->publish($this->stageId, ['31', '45', '100'], ControlChangedPublisher::SOURCE_UPLOAD);

        $this->assertSame(['stage/' . $this->stageId . '/control/100'], array_column($channel->published, 0));
    }

    public function testSaysWhichUploadChangedTheControl()
    {
        $this->watched->watch($this->stageId, '32');
        $channel = $this->_spyChannel();

        (new ControlChangedPublisher($channel, $this->watched))
            ->publish($this->stageId, ['32'], ControlChangedPublisher::SOURCE_UPLOAD, 'upload-abc');

        $payload = ['station' => '32', 'source' => 'upload', 'uploadId' => 'upload-abc'];
        $expected = [['stage/' . $this->stageId . '/control/32', $payload]];
        $this->assertSame($expected, $channel->published);
    }
}
