<?php

declare(strict_types = 1);

namespace Lib;

use App\Controller\ApiController;
use Cake\TestSuite\TestCase;
use Results\Lib\UploadMetrics;
use Results\Model\Entity\Event;
use Results\Model\Table\ClassesTable;

class UploadMetricsTest extends TestCase
{
    protected $fixtures = [
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/tokens/';
    }

    public function testToArray()
    {
        $metrics = new UploadMetrics();
        $metrics->startProcessing();
        $metrics->saveManyOrFail(ClassesTable::load(), []);

        $res = $metrics->toArray('fake_test_type');
        $this->assertEquals(['meta', 'data'], array_keys($res));
        $this->assertEquals(['updated', 'timings', 'humanColor', 'human'], array_keys($res['meta']));
        $this->assertEquals([0], array_keys($res['meta']['human']));
        $this->assertTrue(is_string($res['meta']['human'][0]));
        $this->assertEquals([
            'classes' => 0,
            'runners' => 0,
            'splits' => 0,
            'runnerResults' => 0,
        ], $res['meta']['updated']);
        $this->assertEquals(['processing', 'saving', 'total'], array_keys($res['meta']['timings']));
        $this->assertEquals(['splits', 'total'], array_keys($res['meta']['timings']['processing']));
        $this->assertEquals(['total'], array_keys($res['meta']['timings']['saving']));
    }

    public function testToArrayLegacy()
    {
        $metrics = new UploadMetrics();
        $metrics->startProcessing();
        $metrics->saveManyOrFail(ClassesTable::load(), []);

        $res = $metrics->toArrayLegacy('fake_test_type');
        $this->assertEquals(['meta', 'data'], array_keys($res));
        $this->assertEquals(['updated', 'human'], array_keys($res['meta']));
        $this->assertEquals([0], array_keys($res['meta']['human']));
        $this->assertTrue(is_string($res['meta']['human'][0]));
        $this->assertEquals([
            'classes' => 0,
            'runners' => 0,
        ], $res['meta']['updated']);
    }
}
