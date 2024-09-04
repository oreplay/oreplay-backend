<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Cake\Cache\Cache;
use Cake\I18n\FrozenTime;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Event;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Table\AnswersTable;
use Results\Model\Table\ClassesControlsTable;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\ClubsTable;
use Results\Model\Table\ControlsTable;
use Results\Model\Table\CoursesTable;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\SplitsTable;
use Results\Model\Table\TeamResultsTable;
use Results\Model\Table\TeamsTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ClubsFixture;
use Results\Test\Fixture\ControlsFixture;
use Results\Test\Fixture\ControlTypesFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\ResultTypesFixture;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\SplitsFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\StageTypesFixture;
use Results\Test\Fixture\TokensFixture;

class UploadsControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        ClubsFixture::LOAD,
        ClassesFixture::LOAD,
        RunnersFixture::LOAD,
        ResultTypesFixture::LOAD,
        RunnerResultsFixture::LOAD,
        SplitsFixture::LOAD,
        ControlsFixture::LOAD,
        ControlTypesFixture::LOAD,
        TokensFixture::LOAD,
        StageTypesFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/uploads/';
    }

    public function testAddNew_shouldAddStartDates()
    {
        Cache::clear();
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = ['oreplay_data_transfer' => UploadsControllerHelper::exampleImportSmall()];
        $this->post($this->_getEndpoint(), $data);

        $jsonDecoded = $this->assertJsonResponseOK();
        $decodedData = $jsonDecoded['data'];
        $now = new FrozenTime();
        $expectedMeta = [
            'updated' => [
                'classes' => 2,
                'runners' => 4,
            ],
            'human' => [
                'Updated 2 classes',
                "Updated 4 runners ($now)",
            ]
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']);

        $addedClasses = $ClassesTable->find()
            ->where(['Classes.stage_id' => StagesFixture::STAGE_FEDO_2])
            ->contain(CoursesTable::name())
            ->orderAsc('Classes.oe_key')
            ->all();
        $this->assertEquals(2, count($addedClasses));
        $expectedClasses = ['ME', 'WE'];
        foreach ($addedClasses as $k => $class) {
            $this->assertEquals($expectedClasses[$k], $class->short_name);
            $this->assertEquals($expectedClasses[$k], $class->course->short_name);
        }

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->orderAsc('last_name')
            ->all();

        $this->assertEquals(4, count($res), 'Runner count in db');
        $this->assertEquals(2, count($decodedData[0]['runners']));
        $this->assertEquals(2, count($decodedData[1]['runners']));
        $runnersJson = array_merge($decodedData[0]['runners'], $decodedData[1]['runners']);
        /** @var Runner $value */
        foreach ($res as $key => $value) {
            $this->assertEquals($runnersJson[$key]['last_name'], $value->last_name);
            $this->assertEquals($runnersJson[$key]['first_name'], $value->first_name);
            $this->assertEquals($runnersJson[$key]['sicard'], $value->sicard);
            $this->assertEquals($runnersJson[$key]['bib_number'], $value->bib_number);
            $this->assertEquals($runnersJson[$key]['id'], $value->id);
            $this->assertEquals($runnersJson[$key]['club']['short_name'], $value->club->short_name);
            $this->assertEquals($runnersJson[$key]['runner_results'][0]['start_time'],
                $value->runner_results[0]->start_time->jsonSerialize());
            if ($key === 0) {
                $this->assertEquals('2014-07-06T13:09:01.523+00:00', $runnersJson[$key]['runner_results'][0]['start_time']);
            }
            $this->assertEquals($runnersJson[$key]['runner_results'][0]['id'],
                $value->runner_results[0]->id);
            $this->assertEquals(ResultType::STAGE,
                $value->runner_results[0]->result_type_id);
        }
        $this->assertEquals(3, ClubsTable::load()->find()->all()->count());
        $this->assertEquals(2, CoursesTable::load()->find()->all()->count());
        $this->assertEquals(2, ClassesTable::load()->find()->all()->count());
        $this->assertEquals(0, TeamsTable::load()->find()->all()->count());
        $this->assertEquals(6, RunnersTable::load()->find()->all()->count());
        $this->assertEquals(1, ControlsTable::load()->find()->all()->count());
        $this->assertEquals(0, ClassesControlsTable::load()->find()->all()->count());
        $this->assertEquals(5, RunnerResultsTable::load()->find()->all()->count());
        $this->assertEquals(0, TeamResultsTable::load()->find()->all()->count());
        $this->assertEquals(1, SplitsTable::load()->find()->all()->count());
        $this->assertEquals(0, AnswersTable::load()->find()->all()->count());
    }

    public function testAddNew_shouldRequireAuthenticatedToken()
    {
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = ['oreplay_data_transfer' => UploadsControllerHelper::exampleImportSmall()];
        $this->post($this->_getEndpoint(), $data);
        $this->assertException('Forbidden', 403, 'Invalid Bearer token');
    }

    public function testAddNew_shouldNotUpdateStartListWhenThereAreFinishTimes()
    {
        Cache::clear();
        RunnerResultsTable::load()->updateAll([
            'stage_id' => StagesFixture::STAGE_FEDO_2,
            'finish_time' => new FrozenTime()
        ], ['id' => RunnerResult::FIRST_RES]);
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = ['oreplay_data_transfer' => UploadsControllerHelper::exampleImportSmall()];
        $this->post($this->_getEndpoint(), $data);

        $jsonDecoded = $this->assertJsonResponseOK();
        $now = new FrozenTime();
        $expectedMeta = [
            'updated' => [
                'classes' => 0,
                'runners' => 0,
            ],
            'human' => [
                "[Error - 400] ($now) Cannot add start times when there are already finish times"
            ]
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']);

        $addedClasses = $ClassesTable->find()
            ->where(['Classes.stage_id' => StagesFixture::STAGE_FEDO_2])
            ->contain(CoursesTable::name())
            ->orderAsc('Classes.oe_key')
            ->all();
        $this->assertEquals(1, count($addedClasses));

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->orderAsc('last_name')
            ->all();

        $this->assertEquals(0, count($res), 'Runner count in db');
    }
}
