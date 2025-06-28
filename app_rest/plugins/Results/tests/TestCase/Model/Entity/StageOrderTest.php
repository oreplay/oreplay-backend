<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Entity;

use Cake\TestSuite\TestCase;
use Results\Model\Entity\StageOrder;

class StageOrderTest extends TestCase
{
    public function testSetExtraNote()
    {
        $res = new StageOrder();
        $res->id = 'theID';
        $res->description = 'Some Description';

        $expected = [
            'id' => 'theID',
            'description' => 'Some Description',
        ];
        $this->assertEquals($expected, json_decode(json_encode($res), true));

        $res->setExtraNote('ORG');
        $expected = [
            'id' => 'theID',
            'description' => 'Some Description [ORG]',
        ];
        $this->assertEquals($expected, json_decode(json_encode($res), true));
    }
}
