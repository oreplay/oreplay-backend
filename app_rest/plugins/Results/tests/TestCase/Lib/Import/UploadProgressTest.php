<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Import;

use Cake\TestSuite\TestCase;
use Results\Lib\Import\ClassImportReport;
use Results\Lib\Import\UploadProgress;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Runner;
use Results\Model\Entity\Team;

class UploadProgressTest extends TestCase
{
    protected array $fixtures = [
    ];

    private function _classWith(string $shortName, int $runners, int $teams): ClassEntity
    {
        $class = new ClassEntity();
        $class->id = 'id_of_' . $shortName;
        $class->short_name = $shortName;
        $class->addRunners(array_fill(0, $runners, new Runner()));
        $class->teams = array_fill(0, $teams, new Team());
        return $class;
    }

    public function testReportCountsRunnersAndTeamsApart()
    {
        $report = ClassImportReport::of($this->_classWith('H21', 3, 2));

        $this->assertEquals('id_of_H21', $report->classId);
        $this->assertEquals('H21', $report->shortName);
        $this->assertEquals(3, $report->runnerCount);
        $this->assertEquals(2, $report->teamCount);
        $this->assertEquals(5, $report->participantCount());
    }

    public function testReportOfAClassThatImportedNobody()
    {
        $report = ClassImportReport::of($this->_classWith('H21', 0, 0));

        $this->assertEquals(0, $report->participantCount());
    }

    public function testProgressAddsUpEveryClass()
    {
        $progress = new UploadProgress();

        $progress->add(ClassImportReport::of($this->_classWith('H21', 3, 0)));
        $progress->add(ClassImportReport::of($this->_classWith('D21', 4, 1)));

        $this->assertEquals(2, $progress->classCount());
        $this->assertEquals(8, $progress->participantCount());
        $this->assertEquals('2 classes, 8 participants, last D21', $progress->describe());
    }

    public function testProgressBeforeAnyClassIsImported()
    {
        $progress = new UploadProgress();

        $this->assertEquals(0, $progress->classCount());
        $this->assertEquals('no class needed importing', $progress->describe());
    }
}
