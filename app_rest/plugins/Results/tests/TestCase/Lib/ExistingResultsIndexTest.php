<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib;

use Cake\TestSuite\TestCase;
use Results\Lib\ExistingResultsIndex;
use Results\Model\Entity\Control;

class ExistingResultsIndexTest extends TestCase
{
    protected array $fixtures = [
    ];

    public function testGetExistingControlByStation()
    {
        $index = new ExistingResultsIndex();

        // retrieve empty
        $stationNumber = 131;
        $res = $index->getExistingControlByStation($stationNumber);
        $this->assertNull($res);

        // retrieve new control after storing it
        $control = new Control();
        $control->station = $stationNumber;
        $index->storeControlByStation($control);
        $res = $index->getExistingControlByStation($stationNumber);
        $this->assertEquals($stationNumber, $res->station);
    }
}
