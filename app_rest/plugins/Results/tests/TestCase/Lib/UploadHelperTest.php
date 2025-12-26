<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib;

use Cake\TestSuite\TestCase;
use Results\Lib\UploadHelper;
use Results\Model\Entity\Control;

class UploadHelperTest extends TestCase
{
    protected array $fixtures = [
    ];

    public function testGetExistingControlByStation()
    {
        $helper = new UploadHelper(['fake' => 'data'], 'fake_event_id');

        // retrieve empty
        $stationNumber = 131;
        $res = $helper->getExistingControlByStation($stationNumber);
        $this->assertNull($res);

        // retrieve new control after storing it
        $control = new Control();
        $control->station = $stationNumber;
        $helper->storeControlByStation($control);
        $res = $helper->getExistingControlByStation($stationNumber);
        $this->assertEquals($stationNumber, $res->station);
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
