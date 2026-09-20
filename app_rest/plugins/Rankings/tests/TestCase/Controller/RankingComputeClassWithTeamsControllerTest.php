<?php

declare(strict_types = 1);

namespace Rankings\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Rankings\Controller\RankingComputeClassController;
use Rankings\Model\Table\RankingsTable;
use Rankings\Test\Fixture\RankingsFixture;
use Results\Lib\Consts\StatusCode;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Event;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Stage;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\StageOrdersTable;
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
use Results\Test\Fixture\StageOrdersFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\TeamResultsFixture;
use Results\Test\Fixture\TeamsFixture;

class RankingComputeClassWithTeamsControllerTest extends ApiCommonErrorsTest
{
    protected array $fixtures = [
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        ClubsFixture::LOAD,
        ClassesFixture::LOAD,
        RunnersFixture::LOAD,
        RunnerResultsFixture::LOAD,
        ControlsFixture::LOAD,
        ControlTypesFixture::LOAD,
        TeamsFixture::LOAD,
        TeamResultsFixture::LOAD,
        ResultTypesFixture::LOAD,
        RankingsFixture::LOAD,
        StageOrdersFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/rankings/' . RankingsTable::FIRST_RANKING
            . '/events/' . Event::FIRST_EVENT . '/stages/' . Stage::FIRST_STAGE
            . '/classes/' . ClassEntity::ME . '/compute/';
    }

    private function _storeTeamWithMembers(): void
    {
        $teams = TeamsTable::load();
        $team = $teams->fillNewWithStage(['team_name' => 'Parella alfa'], Event::FIRST_EVENT, Stage::FIRST_STAGE);
        $team->class_id = ClassEntity::ME;
        $team->bib_number = '301';
        $teams->saveOrFail($team);

        $teamResults = TeamResultsTable::load();
        $teamResult = $teamResults->fillNewWithStage([], Event::FIRST_EVENT, Stage::FIRST_STAGE);
        $teamResult->team_id = $team->id;
        $teamResult->result_type_id = ResultType::STAGE;
        $teamResult->position = 1;
        $teamResult->time_seconds = 310;
        $teamResult->status_code = StatusCode::OK;
        $teamResults->saveOrFail($teamResult);

        foreach ([['María del Carmen', 'García Pérez'], ['Juan', 'López']] as [$firstName, $lastName]) {
            $runners = RunnersTable::load();
            $member = $runners->fillNewWithStage([], Event::FIRST_EVENT, Stage::FIRST_STAGE);
            $member->team_id = $team->id;
            $member->first_name = $firstName;
            $member->last_name = $lastName;
            $runners->saveOrFail($member);
        }
    }

    private function _rankedNames(): array
    {
        $ranked = RunnersTable::load()->find()
            ->where(['stage_id' => StagesFixture::STAGE_RANKING])->all()->toList();
        return array_map(fn($runner) => trim($runner->first_name . ' ' . $runner->last_name), $ranked);
    }

    /**
     * A class whose participants are teams used to answer 404, because only runners were collected and
     * a team member is excluded from that query.
     */
    public function testAddNew_shouldRankATeamAsOneEntryNamedAfterItsMembers()
    {
        StageOrdersTable::load()->deleteCache(StagesFixture::STAGE_RANKING);
        $this->_storeTeamWithMembers();

        $this->post($this->_getEndpoint(), ['secret' => RankingComputeClassController::getSecret()]);

        $this->assertJsonResponseOK();
        $this->assertContains('Parella alfa (María García, Juan López)', $this->_rankedNames());
    }
}
