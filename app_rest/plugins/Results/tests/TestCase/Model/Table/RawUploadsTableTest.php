<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Results\Model\Table\RawUploadsTable;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\RawUploadsFixture;
use Results\Test\Fixture\StagesFixture;

class RawUploadsTableTest extends TestCase
{
    protected $fixtures = [
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

    public function testGetReUploadedData(): void
    {
        // get by id
        $data = [
            'raw_upload_id' => RawUploadsFixture::FIRST,
            'stage_id' => StagesFixture::STAGE_RAID,
        ];
        $reUploadedData = $this->RawUploads->getReUploadedData($data, EventsFixture::EVENT_TODAY);
        $expected = [
            'empty' => 'fixture',
            'oreplay_data_transfer' => [
                'event' => [
                    'id' => '1b10cfcc-b3f2-40bb-8dbe-8b24c0-today'
                ]
            ]
        ];
        $this->assertEquals($expected, $reUploadedData);
        // not valid payload
        $data1 = ['raw_upload_id' => 'wrong'];
        $this->assertNull($this->RawUploads->getReUploadedData($data1, EventsFixture::EVENT_TODAY));
    }
}
