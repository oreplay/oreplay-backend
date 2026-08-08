<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib;

use Cake\Http\Exception\InternalErrorException;
use Cake\TestSuite\TestCase;
use Results\Lib\UploadMetrics;

class UploadMetricsTest extends TestCase
{
    protected array $fixtures = [
    ];

    public function testToArray()
    {
        $metrics = new UploadMetrics();

        $res = $metrics->toArray('fake_test_type');
        $this->assertEquals(['meta', 'data'], array_keys($res));
        $this->assertEquals(['updated', 'timings', 'humanColor', 'human'], array_keys($res['meta']));
        $this->assertEquals([0, 1], array_keys($res['meta']['human']));
        $this->assertTrue(is_string($res['meta']['human'][0]));
        $this->assertEquals([
            'classes' => 0,
            'runners' => 0,
            'splits' => 0,
            'runnerResults' => 0,
            'courses' => 0,
        ], $res['meta']['updated']);
        $this->assertEquals(['processing', 'saving', 'total'], array_keys($res['meta']['timings']));
        $this->assertEquals(['courses', 'runners', 'total'], array_keys($res['meta']['timings']['processing']));
        $this->assertEquals(['total'], array_keys($res['meta']['timings']['saving']));
    }

    public function testMeasureFailsOnAnUnknownTimer()
    {
        $metrics = new UploadMetrics();

        $this->expectException(InternalErrorException::class);
        $this->expectExceptionMessage('Unknown upload timer notATimer');
        $metrics->measure('notATimer', fn() => null);
    }

    public function testMeasureCountsANestedTimerOnlyOnce()
    {
        $metrics = new UploadMetrics();
        $fiftyMilliseconds = 50000;

        $metrics->measure(UploadMetrics::SPLITS, function () use ($metrics, $fiftyMilliseconds) {
            $metrics->measure(UploadMetrics::SPLITS, fn() => usleep($fiftyMilliseconds));
        });

        $splits = $metrics->toArray('fake_test_type')['meta']['timings']['processing']['runners']['splits'];
        $this->assertGreaterThanOrEqual(0.05, $splits);
        $this->assertLessThan(0.09, $splits);
    }

    public function testToArrayLegacy()
    {
        $metrics = new UploadMetrics();

        $res = $metrics->toArrayLegacy('fake_test_type');
        $this->assertEquals(['meta', 'data'], array_keys($res));
        $this->assertEquals(['updated', 'humanColor', 'human'], array_keys($res['meta']));
        $this->assertEquals([0], array_keys($res['meta']['human']));
        $this->assertTrue(is_string($res['meta']['human'][0]));
        $this->assertEquals([
            'classes' => 0,
            'runners' => 0,
        ], $res['meta']['updated']);
    }
}
