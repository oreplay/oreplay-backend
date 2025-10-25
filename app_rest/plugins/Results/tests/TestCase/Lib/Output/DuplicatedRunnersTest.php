<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Output;

use Cake\TestSuite\TestCase;
use Results\Lib\Output\DuplicatedRunners;
use Results\Model\Entity\Runner;

class DuplicatedRunnersTest extends TestCase
{
    protected $fixtures = [
    ];

    public function testSetResults()
    {
        $renderer = new DuplicatedRunners();
        $duplicates = $renderer->setResults($this->_getResults());
        $expected = [
            [
                'id' => 449,
                'bib_number' => 4999,
                'full_name' => '',
                'stage' => null,
                'overalls' => null,
            ],
            [
                'id' => 552,
                'bib_number' => 5222,
                'full_name' => '',
                'stage' => null,
                'overalls' => null,
            ],
        ];
        $this->assertEquals($expected, json_decode(json_encode($duplicates), true));
    }

    private function _getResults(): array
    {
        $runnner1 = new Runner();
        $runnner1->id = 449;
        $runnner1->bib_number = 4999;
        $runnner2 = new Runner();
        $runnner2->id = 552;
        $runnner2->bib_number = 5222;
        $runnner3 = new Runner();
        $runnner3->id = 333;
        $runnner3->bib_number = 4999;
        $results = [$runnner1, $runnner2, $runnner3];
        return $results;
    }
}
