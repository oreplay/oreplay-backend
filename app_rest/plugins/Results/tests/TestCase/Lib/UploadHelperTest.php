<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib;

use Cake\TestSuite\TestCase;
use Results\Lib\UploadHelper;
use Results\Model\Entity\Control;

class UploadHelperTest extends TestCase
{
    protected $fixtures = [
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
}
