<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Publish;

use App\Lib\Consts\CacheGrp;
use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;
use Results\Lib\Import\UploadProcessor;
use Results\Lib\Publish\ChannelPublisher;
use Results\Lib\Publish\ControlChangedPublisher;
use Results\Lib\Publish\UploadedControlsPublisher;
use Results\Lib\Publish\WatchedControls;
use Results\Lib\UploadHelper;
use Results\Lib\UploadMetrics;
use Results\Model\Entity\Event;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\UploadLogsTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ClubsFixture;
use Results\Test\Fixture\ControlsFixture;
use Results\Test\Fixture\ControlTypesFixture;
use Results\Test\Fixture\CoursesFixture;
use Results\Test\Fixture\EventsFixture;
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
use Results\Test\Fixture\UploadLogsFixture;
use Results\Test\TestCase\Controller\UploadExamples\ResultExamples;

class UploadedControlsPublisherTest extends TestCase
{
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
        UploadLogsFixture::LOAD,
        RawUploadsFixture::LOAD,
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->_clearUploadCache();
        (new WatchedControls())->watchAll(StagesFixture::STAGE_FEDO_2, ['31', '32', '33', '34']);
    }

    private function _clearUploadCache(): void
    {
        Cache::clearGroup(CacheGrp::UPLOAD_ENTITIES_GROUP, CacheGrp::UPLOAD);
    }

    private function _splitsPayload(int $forEachRunner): array
    {
        $splits = [];
        for ($i = 0; $i < $forEachRunner; $i++) {
            $splits[] = ['station' => (string)(31 + $i), 'reading_time' => 600 + $i * 60];
        }
        return $splits;
    }

    private function _twoRunnersWithSplits(int $secondRunnerSplits): array
    {
        $runners = [];
        foreach ([2, $secondRunnerSplits] as $i => $splitCount) {
            $runners[] = [
                'db_id' => 'db' . $i, 'bib_number' => (string)$i,
                'first_name' => 'First' . $i, 'last_name' => 'Last' . $i,
                'runner_results' => [[
                    'result_type' => ['id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e', 'description' => 'Stage'],
                    'start_time' => 1000, 'finish_time' => 4000, 'time_seconds' => 3000,
                    'position' => $i + 1, 'status_code' => '0',
                    'splits' => $this->_splitsPayload($splitCount),
                ]],
            ];
        }
        return ['oreplay_data_transfer' => [
            'configuration' => [
                'extension' => 'XML', 'utf' => true, 'known_data' => true,
                'contents' => 'ResultList', 'results_type' => 'Breakdown',
                'one_stage' => true, 'source' => 'OEv12', 'iof_version' => '3.0',
            ],
            'event' => ['id' => Event::FIRST_EVENT, 'stages' => [[
                'id' => StagesFixture::STAGE_FEDO_2,
                'classes' => [[
                    'id' => '', 'short_name' => 'DELTA', 'long_name' => 'DELTA',
                    'runners' => $runners, 'teams' => [],
                ]],
            ]]],
        ]];
    }

    private function _spyChannel(): ChannelPublisher
    {
        return new class implements ChannelPublisher {
            public array $channels = [];
            public array $payloads = [];

            public function publish(string $channel, array $payload): void
            {
                $this->channels[] = $channel;
                $this->payloads[] = $payload;
            }
        };
    }

    private function _upload(array $payload, ChannelPublisher $channel, string $uploadId = null): void
    {
        $helper = new UploadHelper($payload, Event::FIRST_EVENT, new UploadMetrics());
        $controls = new ControlChangedPublisher($channel, new WatchedControls());
        $publisher = new UploadedControlsPublisher($controls, $uploadId);
        (new UploadProcessor(ClassesTable::load(), $publisher))->process($helper);
    }

    public function testSignalsEveryStationTheUploadWrote()
    {
        $channel = $this->_spyChannel();

        $this->_upload($this->_twoRunnersWithSplits(3), $channel, 'upload-abc');

        $stage = 'stage/' . StagesFixture::STAGE_FEDO_2 . '/control/';
        $this->assertSame([$stage . '31', $stage . '32', $stage . '33'], $channel->channels);
        $this->assertSame(
            ['station' => '31', 'source' => ControlChangedPublisher::SOURCE_UPLOAD, 'uploadId' => 'upload-abc'],
            $channel->payloads[0]
        );
    }

    public function testSignalsOnlyTheStationsOfTheRunnersWhosePunchesChanged()
    {
        $this->_upload($this->_twoRunnersWithSplits(2), $this->_spyChannel());
        $this->_clearUploadCache();
        $channel = $this->_spyChannel();

        $this->_upload($this->_twoRunnersWithSplits(4), $channel);

        $stage = 'stage/' . StagesFixture::STAGE_FEDO_2 . '/control/';
        $this->assertSame([$stage . '31', $stage . '32', $stage . '33', $stage . '34'], $channel->channels);
    }

    public function testSignalsNothingWhenTheSameUploadArrivesAgain()
    {
        $this->_upload($this->_twoRunnersWithSplits(2), $this->_spyChannel());
        $this->_clearUploadCache();
        $channel = $this->_spyChannel();

        $this->_upload($this->_twoRunnersWithSplits(2), $channel);

        $this->assertSame([], $channel->channels);
    }
}
