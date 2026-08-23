<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Publish;

use App\Lib\Consts\CacheGrp;
use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;
use Results\Lib\Import\UploadProcessor;
use Results\Lib\Publish\ChannelPublisher;
use Results\Lib\Publish\ClassResultsPublisher;
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

class ClassResultsPublisherTest extends TestCase
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
    }

    private function _clearUploadCache(): void
    {
        Cache::clearGroup(CacheGrp::UPLOAD_ENTITIES_GROUP, CacheGrp::UPLOAD);
    }

    private function _splitsPayload(int $forEachRunner): array
    {
        $splits = [];
        for ($i = 0; $i < $forEachRunner; $i++) {
            $splits[] = ['control' => (string)(31 + $i), 'reading_time' => 600 + $i * 60];
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

    private function _upload(array $payload, ChannelPublisher $channel): void
    {
        $helper = new UploadHelper($payload, Event::FIRST_EVENT, new UploadMetrics());
        $processor = new UploadProcessor(ClassesTable::load(), new ClassResultsPublisher($channel));
        $processor->process($helper);
    }

    public function testPushesEveryRunnerButOnlyTheSplitsThatChanged()
    {
        $this->_upload($this->_twoRunnersWithSplits(2), $this->_spyChannel());
        $this->_clearUploadCache();

        // the second runner punches once more, which is what a radio feed sends
        $channel = $this->_spyChannel();
        $this->_upload($this->_twoRunnersWithSplits(3), $channel);

        $this->assertCount(1, $channel->channels);
        $this->assertStringStartsWith('stage/' . StagesFixture::STAGE_FEDO_2 . '/class/', $channel->channels[0]);
        $runners = $channel->payloads[0]['runners'];
        // everybody is pushed: one finisher moves everybody's position
        $this->assertCount(2, $runners);
        $quiet = $runners[0];
        $punched = $runners[1];
        $this->assertArrayNotHasKey('splits', $quiet['stage'],
            'a runner who did not punch again keeps the splits the reader already has');
        $this->assertArrayHasKey('position', $quiet['stage'],
            'the rest of his result still travels, or his position would never move');
        $this->assertCount(3, $punched['stage']['splits']);
    }

    public function testPushesTheSplitsOfEverybodyOnAFirstUpload()
    {
        $channel = $this->_spyChannel();

        $this->_upload($this->_twoRunnersWithSplits(2), $channel);

        foreach ($channel->payloads[0]['runners'] as $runner) {
            $this->assertCount(2, $runner['stage']['splits']);
        }
    }
}
