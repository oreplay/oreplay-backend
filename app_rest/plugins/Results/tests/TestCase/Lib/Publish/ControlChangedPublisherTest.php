<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Publish;

use Cake\TestSuite\TestCase;
use Results\Lib\Publish\ChannelPublisher;
use Results\Lib\Publish\ControlChangedPublisher;

class ControlChangedPublisherTest extends TestCase
{
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

    public function testPublishesEachDistinctStationOnce()
    {
        $channel = $this->_spyChannel();

        (new ControlChangedPublisher($channel))
            ->publish('stage-1', ['100', '31', '100', ''], ControlChangedPublisher::SOURCE_CPI);

        $expected = [
            ['stage/stage-1/control/100', ['station' => '100', 'source' => 'cpi', 'uploadId' => null]],
            ['stage/stage-1/control/31', ['station' => '31', 'source' => 'cpi', 'uploadId' => null]],
        ];
        $this->assertSame($expected, $channel->published);
    }

    public function testSaysWhichUploadChangedTheControl()
    {
        $channel = $this->_spyChannel();

        (new ControlChangedPublisher($channel))
            ->publish('stage-1', ['32'], ControlChangedPublisher::SOURCE_UPLOAD, 'upload-abc');

        $expected = [
            ['stage/stage-1/control/32', ['station' => '32', 'source' => 'upload', 'uploadId' => 'upload-abc']],
        ];
        $this->assertSame($expected, $channel->published);
    }
}
