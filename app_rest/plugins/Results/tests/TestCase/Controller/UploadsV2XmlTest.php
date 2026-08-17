<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Cake\Cache\Cache;
use Results\Lib\Consts\UploadTypes;
use Results\Model\Entity\Event;
use Results\Model\Table\EventsTable;
use Results\Model\Table\RawUploadsTable;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\CourseControlsTable;
use Results\Model\Table\CoursesTable;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\SplitsTable;
use Results\Model\Table\TeamsTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ClubsFixture;
use Results\Test\Fixture\ControlsFixture;
use Results\Test\Fixture\ControlTypesFixture;
use Results\Test\Fixture\CoursesFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\ResultTypesFixture;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\SplitsFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\StageTypesFixture;
use Results\Test\Fixture\TeamResultsFixture;
use Results\Test\Fixture\TeamsFixture;
use Results\Test\Fixture\TokensFixture;

class UploadsV2XmlTest extends ApiCommonErrorsTest
{
    use UploadResponseTrait;

    private const EVENT_TIME_ZONE = 'Europe/Madrid';

    protected array $fixtures = [
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
    ];

    protected function _getEndpointAddingToSwagger(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/uploads/v2/';
    }

    protected function _getEndpoint(): string
    {
        $this->skipNextRequestInSwagger();
        return $this->_getEndpointAddingToSwagger();
    }

    public function setUp(): void
    {
        $this->flushMemcached();
        parent::setUp();
        Cache::clear();
    }

    private function _asset(string $name): string
    {
        return dirname(__DIR__, 2) . '/assets/' . $name;
    }

    /**
     * configRequest() replaces the whole header set, so the Bearer token loadAuthToken() installed has to
     * be repeated here or the upload is rejected as unauthenticated.
     */
    private function _configureXmlRequest(): void
    {
        $this->configRequest(['headers' => [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . TokensFixture::FIRST_TOKEN,
            'Content-Type' => 'application/xml',
        ]]);
    }

    private function _postXml(string $asset, string $query = ''): array
    {
        $this->_configureXmlRequest();
        $this->post($this->_getEndpoint() . $this->_query($query), file_get_contents($this->_asset($asset)));
        return json_decode((string)$this->_getBodyAsString(), true) ?? [];
    }

    private function _query(string $extra): string
    {
        $query = '?stage_id=' . StagesFixture::STAGE_FEDO_2 . '&tz=' . urlencode(self::EVENT_TIME_ZONE);
        return $extra ? $query . '&' . $extra : $query;
    }

    private function _postXmlWithoutTimeZone(string $asset): array
    {
        $this->_configureXmlRequest();
        $this->post($this->_getEndpoint() . '?stage_id=' . StagesFixture::STAGE_FEDO_2,
            file_get_contents($this->_asset($asset)));
        return json_decode((string)$this->_getBodyAsString(), true) ?? [];
    }

    private function _firstStoredStartTime(): string
    {
        $result = RunnerResultsTable::load()->find()
            ->where(['stage_id' => StagesFixture::STAGE_FEDO_2, 'start_time IS NOT' => null])
            ->orderByAsc('start_time')->first();
        return $result->start_time->setTimezone('UTC')->format('Y-m-d H:i:s');
    }

    /**
     * The acceptance criterion for wiring XML in: the same event uploaded as IOF XML and as the
     * oreplay_data_transfer the Java desktop client produced from that very file has to import to the
     * same counters. The JSON side is the client's own output, so nothing here is compared against our
     * own mapper twice.
     */
    public function testAddNew_shouldImportXmlToTheSameCountersAsTheClientsOwnJson()
    {
        $fromXml = $this->_postXml('Splits_CEEBO.xml');
        $this->assertUploadOk('xml');
        $xmlUpdated = $fromXml['meta']['updated'];

        $this->tearDown();
        $this->setUp();
        $classes = json_decode(file_get_contents($this->_asset('Splits_CEEBO-oreplay.json')), true);
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $this->post($this->_getEndpoint(), ['oreplay_data_transfer' => [
            'configuration' => ['contents' => 'ResultList', 'results_type' => 'Breakdown'],
            'event' => ['id' => Event::FIRST_EVENT, 'stages' => [[
                'id' => StagesFixture::STAGE_FEDO_2,
                'classes' => $classes,
            ]]],
        ]]);
        $fromJson = $this->assertUploadOk('json');

        $this->assertEquals($fromJson['meta']['updated'], $xmlUpdated);
        $this->assertEquals(19, $xmlUpdated['classes']);
        $this->assertEquals(194, $xmlUpdated['runners']);
        $this->assertEquals(3347, $xmlUpdated['splits']);
    }

    public function testAddNew_shouldRecordTheDetectedUploadTypeOnTheResults()
    {
        $this->_postXml('iof/splits.xml');
        $this->assertUploadOk();

        $types = RunnerResultsTable::load()->find()
            ->where(['stage_id' => StagesFixture::STAGE_FEDO_2])
            ->all()->extract('upload_type')->toList();
        $this->assertNotEmpty($types);
        $this->assertEquals([UploadTypes::SPLITS], array_values(array_unique($types)));
    }

    /**
     * The whole point of the SplitTimeControls comment: this file is byte-identical to a splits export
     * apart from it, and it must not be treated as one, because the splits path deletes stored splits.
     */
    public function testAddNew_shouldRecogniseARadioExportFromTheCommentAlone()
    {
        $this->_postXml('iof/radio.xml');
        $this->assertUploadOk();

        $types = RunnerResultsTable::load()->find()
            ->where(['stage_id' => StagesFixture::STAGE_FEDO_2])
            ->all()->extract('upload_type')->toList();
        $this->assertEquals([UploadTypes::INTERMEDIATES], array_values(array_unique($types)));
        $intermediate = SplitsTable::load()->find()
            ->where(['stage_id' => StagesFixture::STAGE_FEDO_2, 'is_intermediate' => true])
            ->all()->count();
        $this->assertGreaterThan(0, $intermediate);
    }

    public function testAddNew_shouldSkipAnUnchangedClassOnASecondIdenticalXmlUpload()
    {
        $first = $this->_postXml('iof/splits.xml');
        $this->assertUploadOk();
        $this->assertEquals(1, $first['meta']['updated']['classes']);

        $second = $this->_postXml('iof/splits.xml');
        $this->assertUploadOk();

        $this->assertEquals(0, $second['meta']['updated']['classes'],
            'the canonical upload hash has to recognise the same XML as unchanged');
    }

    public function testAddNew_shouldStoreTheOriginalBytesInRawUploads()
    {
        $this->_postXml('iof/splits.xml');
        $this->assertUploadOk();

        $stored = (string)RawUploadsTable::load()->find()->orderByDesc('created')->first()->file_data;
        $this->assertEquals(file_get_contents($this->_asset('iof/splits.xml')), $stored);
    }

    /**
     * file_data is utf8 and SportSoftware often writes windows-1252, whose bytes MySQL refuses. Such a
     * body is kept base64 rather than mangled or dropped.
     */
    public function testAddNew_shouldStoreANonUtf8DocumentWithoutLosingIt()
    {
        $this->_postXml('Splits_CEEBO.xml');
        $this->assertUploadOk();

        $stored = (string)RawUploadsTable::load()->find()->orderByDesc('created')->first()->file_data;
        $original = file_get_contents($this->_asset('Splits_CEEBO.xml'));
        $this->assertFalse(mb_check_encoding($original, 'UTF-8'), 'the asset must be the non-utf8 case');
        $this->assertEquals($original, base64_decode($stored));
    }

    public function testAddNew_shouldRefuseXmlWithoutAStageId()
    {
        $this->_configureXmlRequest();
        $this->post($this->_getEndpoint(), file_get_contents($this->_asset('iof/splits.xml')));

        $this->assertUploadRejected(400);
        $this->assertStringContainsString('stage_id', (string)$this->_getBodyAsString());
    }

    public function testAddNew_shouldRefuseIofVersion2()
    {
        $this->_postXml('iof/iof_v2.xml');

        $this->assertUploadRejected(400);
        $this->assertStringContainsString('Unsupported IOF version', (string)$this->_getBodyAsString());
    }

    public function testAddNew_shouldRefuseAnUnknownTimeZone()
    {
        $this->_configureXmlRequest();
        $this->post($this->_getEndpoint() . '?stage_id=' . StagesFixture::STAGE_FEDO_2 . '&tz=Mars/Olympus',
            file_get_contents($this->_asset('iof/splits.xml')));

        $this->assertUploadRejected(400);
    }

    public function testAddNew_shouldImportAStartListWithStartTimesAndNoSplits()
    {
        $response = $this->_postXml('iof/starts.xml');
        $this->assertUploadOk();

        $this->assertEquals(1, $response['meta']['updated']['classes']);
        $this->assertEquals(3, $response['meta']['updated']['runners']);
        $this->assertEquals(0, $response['meta']['updated']['splits'] ?? 0);
        $results = RunnerResultsTable::load()->find()
            ->where(['stage_id' => StagesFixture::STAGE_FEDO_2])->all()->toList();
        $this->assertCount(3, $results);
        foreach ($results as $result) {
            $this->assertNotNull($result->start_time);
            $this->assertNull($result->finish_time);
            $this->assertEquals(UploadTypes::START_LIST, $result->upload_type);
        }
    }

    /**
     * UploadsV2Controller refuses a start list once the stage has finish times, so a late start-list
     * export cannot wipe results that are already in. That guard applies to XML unchanged.
     */
    public function testAddNew_shouldRefuseAStartListOnceFinishTimesExist()
    {
        $this->_postXml('iof/splits.xml');
        $this->assertUploadOk('splits first');

        $this->_postXml('iof/starts.xml');

        $this->assertUploadRejected(400);
        $this->assertStringContainsString('finish times', (string)$this->_getBodyAsString());
    }

    /**
     * IOF carries "2025-03-26T08:00:00.000" with no offset, so the event's own time zone decides which
     * instant that is. Europe/Madrid was still on +01:00 on that date, so 08:00 local is 07:00 UTC.
     */
    public function testAddNew_shouldReadNaiveTimesInTheEventsOwnTimeZone()
    {
        $this->assertEquals('Europe/Madrid', EventsTable::load()->getTimezone(Event::FIRST_EVENT));

        $response = $this->_postXmlWithoutTimeZone('iof/starts.xml');
        $this->assertUploadOk();

        $this->assertEquals('2025-03-26 07:00:00', $this->_firstStoredStartTime());
        $this->assertStringNotContainsString('no time zone', implode(' ', $response['meta']['human'] ?? []));
    }

    public function testAddNew_shouldLetTheQueryStringOverrideTheEventsTimeZone()
    {
        $this->_configureXmlRequest();
        $this->post($this->_getEndpoint() . '?stage_id=' . StagesFixture::STAGE_FEDO_2 . '&tz=UTC',
            file_get_contents($this->_asset('iof/starts.xml')));
        $this->assertUploadOk();

        $this->assertEquals('2025-03-26 08:00:00', $this->_firstStoredStartTime());
    }

    /**
     * Reading local times as UTC silently shifts the whole event, so an event with no zone has to say so
     * rather than guess quietly.
     */
    public function testAddNew_shouldWarnWhenTheEventHasNoTimeZone()
    {
        EventsTable::load()->updateAll(['timezone' => ''], ['id' => Event::FIRST_EVENT]);

        $response = $this->_postXmlWithoutTimeZone('iof/starts.xml');
        $this->assertUploadOk();

        $this->assertStringContainsString('no time zone', implode(' ', $response['meta']['human'] ?? []));
        $this->assertEquals('2025-03-26 08:00:00', $this->_firstStoredStartTime());
    }

    /**
     * A relay class carries no individual results: the runners hang off each team, one per leg, which is
     * the shape CourseImporter walks to build the class course (upload-courses.md 8.2).
     */
    public function testAddNew_shouldImportARelayAsTeamsWithOneRunnerPerLeg()
    {
        $response = $this->_postXml('iof/relay.xml');
        $this->assertUploadOk();

        $this->assertEquals(1, $response['meta']['updated']['classes']);
        // meta.updated.runners counts participants: runnerCount + teamCount, so 12 legs and 3 teams
        $this->assertEquals(15, $response['meta']['updated']['runners']);
        $teams = TeamsTable::load()->find()->where(['stage_id' => StagesFixture::STAGE_FEDO_2])->all();
        $this->assertEquals(3, $teams->count());
        $runners = RunnersTable::load()->find()
            ->where(['stage_id' => StagesFixture::STAGE_FEDO_2, 'team_id IS NOT' => null])->all();
        $this->assertEquals(12, $runners->count());
        $legs = RunnerResultsTable::load()->find()
            ->where(['stage_id' => StagesFixture::STAGE_FEDO_2])
            ->all()->extract('leg_number')->toList();
        sort($legs);
        $this->assertEquals([1, 1, 1, 2, 2, 2, 3, 3, 3, 4, 4, 4], $legs);
    }

    /**
     * Each leg declares its own variant, so the class gets a course of its own carrying what the legs
     * share, and every result points at the variant that leg ran.
     */
    public function testAddNew_shouldStoreACourseForTheClassAndOneForEachLegVariant()
    {
        $this->_postXml('iof/relay.xml');
        $this->assertUploadOk();

        $courses = CoursesTable::load()->find()->where(['stage_id' => StagesFixture::STAGE_FEDO_2])->all();
        $this->assertGreaterThan(1, $courses->count(), 'the class course plus one per declared variant');
        $withVariant = RunnerResultsTable::load()->find()
            ->where(['stage_id' => StagesFixture::STAGE_FEDO_2, 'course_id IS NOT' => null])->all()->count();
        $this->assertEquals(12, $withVariant);
        $classCourse = ClassesTable::load()->find()
            ->where(['stage_id' => StagesFixture::STAGE_FEDO_2, 'course_id IS NOT' => null])->first();
        $this->assertNotNull($classCourse);
        $controls = CourseControlsTable::load()->find()
            ->where(['course_id' => $classCourse->course_id])->all()->count();
        $this->assertGreaterThan(0, $controls, 'the common course of the legs has to be stored');
    }
}
