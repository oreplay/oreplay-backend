<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\Fixture\OauthAccessTokensFixture;
use App\Test\Fixture\UsersFixture;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Cake\Cache\Cache;
use RadioRelay\Lib\Cpi\Consts\PunchType;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Event;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Stage;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\SplitsTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ClubsFixture;
use Results\Test\Fixture\ControlsFixture;
use Results\Test\Fixture\ControlTypesFixture;
use Results\Test\Fixture\CoursesFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\FederationsFixture;
use Results\Test\Fixture\RawUploadsFixture;
use Results\Test\Fixture\ResultTypesFixture;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\SplitsFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\StageTypesFixture;
use Results\Test\Fixture\TeamResultsFixture;
use Results\Test\Fixture\TeamsFixture;
use Results\Test\Fixture\TokensFixture;
use Results\Test\Fixture\UsersEventsFixture;
use Results\Test\TestCase\Controller\UploadExamples\IntermediateExamples;

class ControlPunchesFlowsTest extends ApiCommonErrorsTest
{
    use UploadResponseTrait;

    private const string CPI_STATION = '45';
    private const string RADIO_EXPORT_STATION = '32';

    protected array $fixtures = [
        FederationsFixture::LOAD,
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
        CoursesFixture::LOAD,
        TeamsFixture::LOAD,
        TeamResultsFixture::LOAD,
        OauthAccessTokensFixture::LOAD,
        UsersFixture::LOAD,
        UsersEventsFixture::LOAD,
        RawUploadsFixture::LOAD,
    ];

    public function setUp(): void
    {
        $this->flushMemcached();
        parent::setUp();
    }

    protected function _getEndpoint(): string
    {
        return $this->_punchesEndpoint(Stage::FIRST_STAGE, self::CPI_STATION);
    }

    public function testGetList_showsTheRunnerOfACpiPunchAtANewControl()
    {
        $this->_postCpiPunchOfTheFirstRunner();

        $bodyDecoded = $this->_getPunches(Stage::FIRST_STAGE, self::CPI_STATION);

        $this->assertSame(1, $bodyDecoded['total']);
        $this->assertTrue($bodyDecoded['data'][0]['split']['is_intermediate']);
        $this->assertSame(Runner::FIRST_RUNNER, $bodyDecoded['data'][0]['runner']['id']);
    }

    public function testGetList_showsTheRunnerOfACpiPunchBeforeAnyResultIsStored()
    {
        RunnerResultsTable::load()->deleteAll(['id' => RunnerResult::FIRST_RES]);
        $this->_postCpiPunchOfTheFirstRunner();

        $bodyDecoded = $this->_getPunches(Stage::FIRST_STAGE, self::CPI_STATION);

        $this->assertSame(1, $bodyDecoded['total']);
        $this->assertSame(Runner::FIRST_RUNNER, $bodyDecoded['data'][0]['runner']['id']);
    }

    public function testGetList_showsTheRunnersOfAnXmlRadioExport()
    {
        $this->_uploadToFedoStage(IntermediateExamples::intermediateResults());

        $bodyDecoded = $this->_getPunches(StagesFixture::STAGE_FEDO_2, self::RADIO_EXPORT_STATION);

        $this->assertSame(['Natalia', 'Javier'], $this->_firstNames($bodyDecoded));
        $this->assertSame([true, true], array_column(array_column($bodyDecoded['data'], 'split'), 'is_intermediate'));
    }

    public function testGetList_showsEachRunnerOnceWhenTheDownloadFollowsTheRadioExport()
    {
        $radioExport = IntermediateExamples::intermediateResults();
        $this->_uploadToFedoStage($radioExport);
        $this->_uploadToFedoStage($this->_asDownload($radioExport));

        $bodyDecoded = $this->_getPunches(StagesFixture::STAGE_FEDO_2, self::RADIO_EXPORT_STATION);

        $this->assertSame(['Natalia', 'Javier'], $this->_firstNames($bodyDecoded));
        $this->assertSame(2, $bodyDecoded['total']);
    }

    public function testGetList_showsEachRunnerOnceWhenTheRadioExportFollowsTheDownload()
    {
        $radioExport = IntermediateExamples::intermediateResults();
        $this->_uploadToFedoStage($this->_asDownload($radioExport));
        $this->_uploadToFedoStage($radioExport);

        $bodyDecoded = $this->_getPunches(StagesFixture::STAGE_FEDO_2, self::RADIO_EXPORT_STATION);

        $this->assertSame(['Natalia', 'Javier'], $this->_firstNames($bodyDecoded));
        $this->assertSame(2, $bodyDecoded['total']);
    }

    public function testGetList_showsEachRunnerOnceWhenTheDownloadFollowsACpiPunchStoredBeforeAnyResult()
    {
        $radioExport = IntermediateExamples::intermediateResults();
        $this->_uploadToFedoStage($radioExport);
        $this->_removeTheStoredResultsOfTheFedoStage();
        $this->_postCpiPunch(StagesFixture::STAGE_FEDO_2, '8000001', self::RADIO_EXPORT_STATION, '2024-01-16', '10:56');
        $this->_uploadToFedoStage($this->_asDownload($radioExport));

        $bodyDecoded = $this->_getPunches(StagesFixture::STAGE_FEDO_2, self::RADIO_EXPORT_STATION);

        $this->assertSame(['Natalia', 'Javier'], $this->_firstNames($bodyDecoded));
        $this->assertSame(2, $bodyDecoded['total']);
    }

    private function _punchesEndpoint(string $stageId, string $station): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/stages/' . $stageId
            . '/controls/' . $station . '/punches/';
    }

    private function _getPunches(string $stageId, string $station): array
    {
        $this->skipNextRequestInSwagger();
        $this->get($this->_punchesEndpoint($stageId, $station));
        return $this->assertJsonResponseOK();
    }

    private function _firstNames(array $bodyDecoded): array
    {
        return array_map(fn (array $row) => strtok($row['runner']['full_name'] ?? '', ' '), $bodyDecoded['data']);
    }

    private function _postCpiPunchOfTheFirstRunner(): void
    {
        $this->_postCpiPunch(Stage::FIRST_STAGE, '2009933', self::CPI_STATION, '2025-03-08', '12:50');
    }

    private function _postCpiPunch(string $stageId, string $siCard, string $station, string $date, string $time): void
    {
        $this->skipNextRequestInSwagger();
        $this->post(ApiController::ROUTE_PREFIX . '/radios/cpi/', [
            'order' => 'ProcessPunches',
            'data' => [$stageId, Event::FIRST_EVENT . TokensFixture::FIRST_TOKEN, '+01:00'],
            'punches' => [[
                'date' => $date,
                'raw' => '02d30d80160f85d41b01013c1e7400019db903',
                'reading' => $date . ' 05:58:26',
                'sicard' => $siCard,
                'station' => $station,
                'time' => $time,
                'battery' => '9',
                'type' => PunchType::SI_CARD,
            ]],
        ]);
        $this->assertJsonResponseOK();
    }

    private function _removeTheStoredResultsOfTheFedoStage(): void
    {
        $inFedoStage = ['stage_id' => StagesFixture::STAGE_FEDO_2];
        SplitsTable::load()->deleteAll($inFedoStage);
        RunnerResultsTable::load()->deleteAll($inFedoStage);
        ClassesTable::load()->updateAll(['upload_hash' => null], $inFedoStage);
        Cache::clear();
    }

    private function _uploadToFedoStage(array $payload): void
    {
        ClassesTable::load()->updateAll(['stage_id' => StagesFixture::STAGE_FEDO_2], ['id' => ClassEntity::ME]);
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $this->skipNextRequestInSwagger();
        $this->post(
            ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/uploads/v2/',
            ['oreplay_data_transfer' => $payload]
        );
        $this->assertUploadOk();
    }

    private function _asDownload(array $radioExport): array
    {
        $radioExport['configuration']['results_type'] = 'Breakdown';
        return $radioExport;
    }
}
