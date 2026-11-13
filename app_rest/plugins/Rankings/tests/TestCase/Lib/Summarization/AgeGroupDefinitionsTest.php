<?php

declare(strict_types = 1);

namespace Rankings\Test\Lib\Summarization;

use Cake\TestSuite\TestCase;
use Rankings\Lib\Summarization\Settings\AgeGroupDefinitions;

class AgeGroupDefinitionsTest extends TestCase
{
    protected array $fixtures = [
    ];

    public function testGetAgeGroup()
    {
        $result = AgeGroupDefinitions::getAgeSettings('F-12');
        $this->assertEquals('previous_absolute', $result->getAgeGroup());

        $result = AgeGroupDefinitions::getAgeSettings('F-E');
        $this->assertEquals('absolute', $result->getAgeGroup());

        $result = AgeGroupDefinitions::getAgeSettings('M-50');
        $this->assertEquals('veteran', $result->getAgeGroup());
    }
}
