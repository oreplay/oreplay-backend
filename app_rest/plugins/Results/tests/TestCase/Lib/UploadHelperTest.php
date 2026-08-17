<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib;

use Cake\TestSuite\TestCase;
use Results\Lib\UploadConfigChecker;
use Results\Lib\UploadHelper;
use Results\Lib\UploadMetrics;

class UploadHelperTest extends TestCase
{
    protected array $fixtures = [
    ];

    private function _helperWithChecker(string $eventId): UploadHelper
    {
        $data = ['oreplay_data_transfer' => [
            'event' => ['id' => $eventId, 'stages' => [['id' => 'fake_stage_id', 'classes' => []]]],
        ]];
        $helper = new UploadHelper($data, $eventId, new UploadMetrics());
        $helper->setConfigChecker(UploadConfigChecker::fromPayload($data)->validateStructure($eventId));
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
        $helper = new UploadHelper(['fake' => 'data'], 'fake_event_id', new UploadMetrics());

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

    public function testMd5Encode_shouldNotDependOnKeyOrder()
    {
        $this->assertEquals(
            UploadHelper::md5Encode(['a' => 1, 'b' => 2]),
            UploadHelper::md5Encode(['b' => 2, 'a' => 1])
        );
    }

    public function testMd5Encode_shouldNotDistinguishANumberFromItsStringForm()
    {
        $this->assertEquals(
            UploadHelper::md5Encode(['controls' => 14]),
            UploadHelper::md5Encode(['controls' => '14'])
        );
    }

    /**
     * A field the JSON client sends empty is simply absent from the XML, which must not make the
     * class look changed.
     */
    public function testMd5Encode_shouldTreatEmptyValuesAsAbsent()
    {
        $hash = UploadHelper::md5Encode(['short_name' => 'E']);
        $this->assertEquals($hash, UploadHelper::md5Encode(['short_name' => 'E', 'id' => '']));
        $this->assertEquals($hash, UploadHelper::md5Encode(['short_name' => 'E', 'uuid' => null]));
        $this->assertEquals($hash, UploadHelper::md5Encode(['short_name' => 'E', 'course' => []]));
    }

    public function testMd5Encode_shouldKeepAListInItsOwnOrder()
    {
        $this->assertNotEquals(
            UploadHelper::md5Encode(['splits' => [['station' => '31'], ['station' => '32']]]),
            UploadHelper::md5Encode(['splits' => [['station' => '32'], ['station' => '31']]])
        );
    }

    /**
     * The point of canonicalising: the same class reaching us as JSON and as IOF XML must produce one
     * hash, or each source invalidates everything the other wrote and a big event never converges.
     */
    public function testMd5Encode_shouldMatchForTheSameClassFromJsonAndFromXml()
    {
        $fromJson = [
            'id' => '',
            'uuid' => '',
            'oe_key' => '1',
            'short_name' => 'E',
            'long_name' => 'Elite',
            'course' => ['id' => '', 'controls' => 14, 'distance' => '5000.0', 'climb' => null],
        ];
        $fromXml = [
            'short_name' => 'E',
            'course' => ['controls' => '14', 'distance' => '5000.0'],
            'long_name' => 'Elite',
            'oe_key' => '1',
        ];

        $this->assertEquals(UploadHelper::md5Encode($fromJson), UploadHelper::md5Encode($fromXml));
    }
}
