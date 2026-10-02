<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Results\Model\Entity\Event;
use Results\Model\Table\RawUploadsTable;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\RawUploadsFixture;
use Results\Test\Fixture\StagesFixture;

class RawUploadsTableTest extends TestCase
{
    protected array $fixtures = [
        RawUploadsFixture::LOAD,
    ];

    private RawUploadsTable $RawUploads;

    public function setUp(): void
    {
        parent::setUp();
        $this->RawUploads = RawUploadsTable::load();
    }

    public function testHardDeleteOld(): void
    {
        $raw = $this->RawUploads->findById(RawUploadsFixture::FIRST)->first();
        $this->assertNotEmpty($raw);

        $this->RawUploads->hardDeleteOld();

        $raw = $this->RawUploads->findById(RawUploadsFixture::FIRST)->first();
        $this->assertEmpty($raw);
    }

    public function testFindReUploadSource_shouldFindTheStoredUploadOfAReplayRequest(): void
    {
        $source = $this->RawUploads->findReUploadSource([
            'raw_upload_id' => RawUploadsFixture::FIRST,
            'stage_id' => StagesFixture::STAGE_RAID,
        ]);

        $this->assertEquals(Event::FIRST_EVENT, $source->event_id,
            'the caller is authorised against this event before the upload is replayed');
    }

    public function testFindReUploadSource_shouldIgnoreAnOrdinaryUpload(): void
    {
        $this->assertNull($this->RawUploads->findReUploadSource(['raw_upload_id' => 'wrong']));
    }

    public function testReUploadedDataOf_shouldMoveTheUploadIntoTheTargetEvent(): void
    {
        $source = $this->RawUploads->get(RawUploadsFixture::FIRST);

        $reUploaded = $this->RawUploads->reUploadedDataOf($source, EventsFixture::EVENT_TODAY,
            StagesFixture::STAGE_RAID);

        $expected = [
            'empty' => 'fixture',
            'oreplay_data_transfer' => [
                'event' => [
                    'id' => EventsFixture::EVENT_TODAY,
                ],
            ],
        ];
        $this->assertEquals($expected, $reUploaded);
    }
}
