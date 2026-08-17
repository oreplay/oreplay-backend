<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Cake\Cache\Cache;
use Results\Lib\Consts\UploadTypes;
use Results\Model\Entity\Event;
use Results\Model\Table\RawUploadsTable;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\SplitsTable;
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

    /**
     * Memory is bounded by the biggest class rather than by the file, so a single enormous class could
     * still exhaust it. The guard goes when that is fixed.
     */
    public function testAddNew_shouldRefuseABodyLargerThanTheGuard()
    {
        $oversized = '<?xml version="1.0" encoding="UTF-8"?><ResultList iofVersion="3.0" creator="x">'
            . str_repeat('<!-- padding -->', 200000) . '</ResultList>';
        $this->_configureXmlRequest();
        $this->post($this->_getEndpoint() . $this->_query(''), $oversized);

        $this->assertUploadRejected(400);
        $this->assertStringContainsString('Split the export by class', (string)$this->_getBodyAsString());
    }
}
