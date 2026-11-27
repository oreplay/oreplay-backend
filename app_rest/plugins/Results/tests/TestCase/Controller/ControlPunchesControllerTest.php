<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Cake\I18n\FrozenTime;
use Results\Model\Entity\Event;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Split;
use Results\Model\Entity\Stage;
use Results\Model\Entity\Team;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\SplitsTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ClubsFixture;
use Results\Test\Fixture\ControlsFixture;
use Results\Test\Fixture\ControlTypesFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\SplitsFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\TeamResultsFixture;
use Results\Test\Fixture\TeamsFixture;

class ControlPunchesControllerTest extends ApiCommonErrorsTest
{
    protected array $fixtures = [
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        ClassesFixture::LOAD,
        ClubsFixture::LOAD,
        ControlTypesFixture::LOAD,
        ControlsFixture::LOAD,
        SplitsFixture::LOAD,
        RunnersFixture::LOAD,
        RunnerResultsFixture::LOAD,
        TeamsFixture::LOAD,
        TeamResultsFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/stages/'
            . Stage::FIRST_STAGE . '/controls/31/punches/';
    }

    public function testGetList_teamAndIndividualPunches()
    {
        $this->skipNextRequestInSwagger();
        $this->get($this->_getEndpoint());

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertSame(2, $bodyDecoded['total']);
        $this->assertSame(10, $bodyDecoded['limit']);
        [$teamPunch, $runnerPunch] = $bodyDecoded['data'];

        $this->assertSame(SplitsFixture::SPLIT_2, $teamPunch['split']['id']);
        $this->assertSame('2024-01-03T11:00:20.321+00:00', $teamPunch['split']['reading_time']);
        $this->assertNull($teamPunch['runner']);
        $this->assertSame(Team::FIRST_TEAM, $teamPunch['team']['id']);
        $this->assertArrayHasKey('runners', $teamPunch['team']);

        $this->assertSame(SplitsFixture::SPLIT_1, $runnerPunch['split']['id']);
        $this->assertSame(Runner::FIRST_RUNNER, $runnerPunch['runner']['id']);
        $this->assertSame(ClubsFixture::CLUB_1, $runnerPunch['runner']['club']['id']);
        $this->assertSame('ME', $runnerPunch['runner']['class']['short_name']);
        $this->assertNotEmpty($runnerPunch['runner']['stage']['splits']);
        $this->assertNull($runnerPunch['team']);
    }

    public function testGetList()
    {
        RunnersTable::load()->updateAll(['team_id' => Team::FIRST_TEAM], ['id' => Runner::FIRST_RUNNER]);
        SplitsTable::load()->softDeleteMany([SplitsFixture::SPLIT_1]);
        $radioId = $this->_saveRadioPunch(Runner::FIRST_RUNNER, '2024-01-04 09:00:00');
        $this->get($this->_getEndpoint() . '?limit=20&page=1');

        $relayPunch = $this->assertJsonResponseOK()['data'][0];
        $this->assertSame($radioId, $relayPunch['split']['id']);
        $this->assertSame(Runner::FIRST_RUNNER, $relayPunch['runner']['id']);
        $this->assertSame(Team::FIRST_TEAM, $relayPunch['team']['id']);
    }

    public function testGetList_paginates()
    {
        $this->skipNextRequestInSwagger();
        $this->get($this->_getEndpoint() . '?limit=1&page=2');

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertSame(2, $bodyDecoded['total']);
        $this->assertSame([SplitsFixture::SPLIT_1], array_column(array_column($bodyDecoded['data'], 'split'), 'id'));
        $this->assertArrayHasKey('prev', $bodyDecoded['_links']);

        $this->skipNextRequestInSwagger();
        $this->get($this->_getEndpoint() . '?page=99');

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertSame(2, $bodyDecoded['total']);
        $this->assertSame([], $bodyDecoded['data']);
    }

    public function testGetList_orphanPunchHasNeitherRunnerNorTeam()
    {
        $orphanIds = [SplitsFixture::SPLIT_1, SplitsFixture::SPLIT_1_RADIO];
        SplitsTable::load()->updateAll(['runner_result_id' => null], ['id IN' => $orphanIds]);
        $this->skipNextRequestInSwagger();
        $this->get($this->_getEndpoint());

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertSame(2, $bodyDecoded['total']);
        $orphanPunch = $bodyDecoded['data'][1];
        $this->assertSame(SplitsFixture::SPLIT_1, $orphanPunch['split']['id']);
        $this->assertNull($orphanPunch['runner']);
        $this->assertNull($orphanPunch['team']);
    }

    public function testGetList_rejectsALimitOverTheCap()
    {
        $this->skipNextRequestInSwagger();
        $this->get($this->_getEndpoint() . '?limit=311');
        $this->assertResponseCode(400);
    }

    private function _saveRadioPunch(string $runnerId, string $readingTime): string
    {
        $Splits = SplitsTable::load();
        /** @var Split $split */
        $split = $Splits->fillNewWithStage([
            'station' => '31',
            'reading_time' => new FrozenTime($readingTime),
            'is_intermediate' => true,
        ], Event::FIRST_EVENT, Stage::FIRST_STAGE);
        $split->runner_id = $runnerId;
        $split->runner_result_id = RunnerResult::FIRST_RES;
        $split->control_id = ControlsFixture::CONTROL_31;
        return $Splits->saveOrFail($split)->id;
    }
}
