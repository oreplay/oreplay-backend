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

    private function _humanOf(UploadMetrics $metrics): string
    {
        return $metrics->toArray('fake_test_type')['meta']['human'][0];
    }

    public function testWarnings_shouldShowOnlyTheLastOrdinaryOne()
    {
        $metrics = new UploadMetrics();
        $metrics->setWarning('Team without runners');
        $metrics->setWarning('Runner without runner_results');

        $human = $this->_humanOf($metrics);
        $this->assertStringContainsString('Runner without runner_results', $human);
        $this->assertStringNotContainsString('Team without runners', $human,
            'an ordinary warning is superseded by the next one');
    }

    public function testWarnings_shouldShowEveryDataLossWarning()
    {
        $metrics = new UploadMetrics();
        $metrics->setDataLossWarning('Not saved in database: 5 rows of class ME');
        $metrics->setDataLossWarning('Duplicated runner Maria 101');

        $human = $this->_humanOf($metrics);
        $this->assertStringContainsString('Not saved in database: 5 rows of class ME', $human);
        $this->assertStringContainsString('Duplicated runner Maria 101', $human,
            'losing data twice must be reported twice, not collapsed to the last one');
    }

    public function testWarnings_shouldHideOrdinaryOnesWhenDataWasLost()
    {
        $metrics = new UploadMetrics();
        $metrics->setDataLossWarning('Not saved in database: 5 rows of class ME');
        $metrics->setWarning('It is taking too long, you need to upload again to finish processing');

        $human = $this->_humanOf($metrics);
        $this->assertStringContainsString('Not saved in database', $human,
            'the data loss must survive a later ordinary warning');
        $this->assertStringNotContainsString('taking too long', $human,
            'the ordinary warning gives way so the data loss is not buried');
    }

    public function testWarnings_shouldStoreAtMostTenOfEachType()
    {
        $metrics = new UploadMetrics();
        for ($i = 1; $i <= 12; $i++) {
            $metrics->setDataLossWarning('Lost row ' . $i);
        }

        $human = $this->_humanOf($metrics);
        $this->assertEquals(10, substr_count($human, 'Lost row '), 'ten kept, so a flood cannot exhaust memory');
        $this->assertStringContainsString('Lost row 12', $human, 'the most recent are the ones kept');
        $this->assertStringNotContainsString('Lost row 1 ', $human);
    }
}
