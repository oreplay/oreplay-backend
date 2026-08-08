<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib;

use Cake\TestSuite\TestCase;
use Results\Lib\UploadConfigChecker;
use Results\Lib\UploadHelper;

class UploadHelperTest extends TestCase
{
    protected array $fixtures = [
    ];

    private function _helperWithChecker(string $eventId): UploadHelper
    {
        $data = ['oreplay_data_transfer' => [
            'event' => ['id' => $eventId, 'stages' => [['id' => 'fake_stage_id', 'classes' => []]]],
        ]];
        $helper = new UploadHelper($data, $eventId);
        $helper->setConfigChecker((new UploadConfigChecker($data))->validateStructure($eventId));
        return $helper;
    }

    public function testInClassSharesTheUploadWideCollaborators()
    {
        $helper = $this->_helperWithChecker('fake_event_id');

        $inClass = $helper->inClass('fake_class_id');

        // shared, or per class counters would reset and existing results would be re-queried
        $this->assertSame($helper->getMetrics(), $inClass->getMetrics());
        $this->assertSame($helper->getExistingResults(), $inClass->getExistingResults());
        $this->assertSame($helper->getChecker(), $inClass->getChecker());
    }

    public function testInClassLeavesTheOriginalUntouched()
    {
        $helper = $this->_helperWithChecker('fake_event_id');

        $inClass = $helper->inClass('fake_class_id');

        $this->assertEquals('fake_class_id', $inClass->getContext()->getClassId());
        $this->assertEquals('', $helper->getContext()->getClassId());
        $this->assertEquals('fake_stage_id', $inClass->getContext()->getStageId());
        $this->assertEquals('fake_event_id', $inClass->getContext()->getEventId());
    }

    public function testIsArrayWithoutValues()
    {
        $helper = new UploadHelper(['fake' => 'data'], 'fake_event_id');

        // empty array
        $arr = [];
        $res = $helper->isArrayWithoutValues($arr);
        $this->assertTrue($res);

        // array with null values
        $arr = [null, null];
        $res = $helper->isArrayWithoutValues($arr);
        $this->assertTrue($res);

        // array with empty strings
        $arr = ['', ''];
        $res = $helper->isArrayWithoutValues($arr);
        $this->assertTrue($res);

        // array with keys and empty values
        $arr = ['a' => '', 'b' => null];
        $res = $helper->isArrayWithoutValues($arr);
        $this->assertTrue($res);

        // array with at least one value
        $arr = [null, 'value', ''];
        $res = $helper->isArrayWithoutValues($arr);
        $this->assertFalse($res);

        // array with keys and at least one value
        $arr = ['a' => null, 'b' => 'value', 'c' => ''];
        $res = $helper->isArrayWithoutValues($arr);
        $this->assertFalse($res);
    }
}
