<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Import;

use App\Lib\Consts\CacheGrp;
use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;
use Results\Lib\Import\ClassImportReport;
use Results\Lib\Import\UploadProcessor;
use Results\Lib\Publish\UploadProgressPublisher;
use Results\Lib\Publish\UploadPublisher;
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

class UploadProcessorTest extends TestCase
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

    // getByShortName() memoises classes in memcached, so without this an upload reads a class from before
    // the previous one saved its hash. Every real request clears the same group, see the upload controllers.
    private function _clearUploadCache(): void
    {
        Cache::clearGroup(CacheGrp::UPLOAD_ENTITIES_GROUP, CacheGrp::UPLOAD);
    }

    private function _helperForTwoClasses(): UploadHelper
    {
        $data = ['oreplay_data_transfer' => ResultExamples::resultImport2CategoriesStarts()];
        return new UploadHelper($data, Event::FIRST_EVENT, new UploadMetrics());
    }

    private function _process(UploadPublisher $publisher, UploadHelper $helper = null): array
    {
        $helper = $helper ?: $this->_helperForTwoClasses();
        $progress = (new UploadProcessor(ClassesTable::load(), $publisher))->process($helper);
        return [$progress, $helper];
    }

    public static function infoOfTheNewestLog(): ?string
    {
        $log = UploadLogsTable::load()->find()
            ->where(['stage_id' => StagesFixture::STAGE_FEDO_2])
            ->orderByDesc('modified')->first();
        return $log?->info;
    }

    public static function countOfLogs(): int
    {
        return UploadLogsTable::load()->find()
            ->where(['stage_id' => StagesFixture::STAGE_FEDO_2])->all()->count();
    }

    private function _spyPublisher(): UploadPublisher
    {
        return new class implements UploadPublisher {
            public array $stageIds = [];
            public array $classNames = [];
            public array $participantCounts = [];

            public function classImported(string $stageId, ClassImportReport $report): void
            {
                $this->stageIds[] = $stageId;
                $this->classNames[] = $report->shortName;
                $this->participantCounts[] = $report->participantCount();
            }
        };
    }

    public function testPublishesOnceForEveryImportedClass()
    {
        $publisher = $this->_spyPublisher();

        [$progress] = $this->_process($publisher);

        $this->assertEquals(['U-10', 'O ROJO F'], $publisher->classNames);
        $this->assertEquals([1, 1], $publisher->participantCounts);
        $this->assertEquals([StagesFixture::STAGE_FEDO_2, StagesFixture::STAGE_FEDO_2], $publisher->stageIds);
        $this->assertEquals(2, $progress->classCount());
        $this->assertEquals(2, $progress->participantCount());
    }

    public function testPublishesNothingWhenNoClassChanged()
    {
        $this->_process($this->_spyPublisher());
        $this->_clearUploadCache();
        $publisher = $this->_spyPublisher();

        // the same payload again: every class hash already matches, so nothing is imported
        [$progress] = $this->_process($publisher);

        $this->assertEquals([], $publisher->classNames);
        $this->assertEquals(0, $progress->classCount());
    }

    public function testTheProgressPublisherRecordsWhileStillImporting()
    {
        $helper = $this->_helperForTwoClasses();
        $helper->validateConfigChecker();
        $log = UploadLogsTable::load()->saveUploadLog($helper);
        // two listeners on one event, which is what the real push will need alongside this recorder
        $publisher = new class (new UploadProgressPublisher($log)) implements UploadPublisher {
            public array $infoSeenSoFar = [];

            public function __construct(private readonly UploadPublisher $recorder)
            {
            }

            public function classImported(string $stageId, ClassImportReport $report): void
            {
                $this->recorder->classImported($stageId, $report);
                $this->infoSeenSoFar[] = UploadProcessorTest::infoOfTheNewestLog();
            }
        };

        $this->_process($publisher, $helper);

        // read halfway through the upload: a client polling the log sees it advance, and an upload killed
        // between the two classes would leave the first line behind rather than nothing at all
        $this->assertEquals([
            '1 classes, 1 participants, last U-10',
            '2 classes, 2 participants, last O ROJO F',
        ], $publisher->infoSeenSoFar);
    }

    public function testTheProcessorWritesNoLogOfItsOwn()
    {
        // the row belongs to the controllers: v1 writes it once the upload is over, v2 up front
        $this->_process($this->_spyPublisher());

        $this->assertSame(0, self::countOfLogs());
    }
}
