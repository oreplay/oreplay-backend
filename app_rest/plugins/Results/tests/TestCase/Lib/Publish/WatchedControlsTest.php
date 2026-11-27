<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Publish;

use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use Results\Lib\Publish\WatchedControls;

class WatchedControlsTest extends TestCase
{
    private string $stageId;
    private string $otherStageId;

    public function setUp(): void
    {
        parent::setUp();
        $this->stageId = Text::uuid();
        $this->otherStageId = Text::uuid();
    }

    public function testReturnsOnlyTheWatchedStationsOfTheStage()
    {
        $watched = new WatchedControls();
        $watched->watch($this->stageId, '31');
        $watched->watch($this->otherStageId, '100');

        $this->assertSame(['31'], $watched->watchedAmong($this->stageId, ['31', '100', '45']));
    }

    public function testNothingIsWatchedBeforeAnyoneSubscribes()
    {
        $this->assertSame([], (new WatchedControls())->watchedAmong($this->stageId, ['31', '100']));
    }

    public function testAcceptsStationsThatAreNotValidCacheKeys()
    {
        $watched = new WatchedControls();
        $watched->watch($this->stageId, 'F 1');

        $this->assertSame(['F 1'], $watched->watchedAmong($this->stageId, ['F 1']));
    }
}
