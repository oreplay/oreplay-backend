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
        $expected = [
            'total' => 2,
            'limit' => 10,
            'punches' => [
                [
                    'split_id' => SplitsFixture::SPLIT_2,
                    'reading_time' => '2024-01-03T11:00:20.321+00:00',
                    'runner_id' => null,
                    'club_id' => null,
                    'class_short_name' => null,
                    'runner_has_stage_result' => false,
                    'runner_carries_splits' => false,
                    'team_id' => Team::FIRST_TEAM,
                    'team_lists_runners' => true,
                    'team_carries_splits' => false,
                ],
                [
                    'split_id' => SplitsFixture::SPLIT_1,
                    'reading_time' => '2024-01-02T10:00:10.321+00:00',
                    'runner_id' => Runner::FIRST_RUNNER,
                    'club_id' => ClubsFixture::CLUB_1,
                    'class_short_name' => 'ME',
                    'runner_has_stage_result' => true,
                    'runner_carries_splits' => false,
                    'team_id' => null,
                    'team_lists_runners' => false,
                    'team_carries_splits' => false,
                ],
            ],
        ];

        $this->skipNextRequestInSwagger();
        $this->get($this->_getEndpoint());

        $bodyDecoded = $this->assertJsonResponseOK();
        $page = [
            'total' => $bodyDecoded['total'],
            'limit' => $bodyDecoded['limit'],
            'punches' => array_map([$this, '_identifyPunch'], $bodyDecoded['data']),
        ];
        $this->assertSame($expected, $page);
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

    private function _identifyPunch(array $punch): array
    {
        $runner = $punch['runner'];
        $team = $punch['team'];
        return [
            'split_id' => $punch['split']['id'],
            'reading_time' => $punch['split']['reading_time'],
            'runner_id' => $runner['id'] ?? null,
            'club_id' => $runner['club']['id'] ?? null,
            'class_short_name' => $runner['class']['short_name'] ?? null,
            'runner_has_stage_result' => isset($runner['stage']['id']),
            'runner_carries_splits' => !empty($runner['stage']['splits']),
            'team_id' => $team['id'] ?? null,
            'team_lists_runners' => isset($team['runners']),
            'team_carries_splits' => $this->_teamCarriesSplits($team),
        ];
    }

    private function _teamCarriesSplits(?array $team): bool
    {
        $legResults = array_column($team['runners'] ?? [], 'stage');
        $legSplits = array_filter(array_column($legResults, 'splits'));
        return !empty($team['stage']['splits']) || !empty($legSplits);
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
