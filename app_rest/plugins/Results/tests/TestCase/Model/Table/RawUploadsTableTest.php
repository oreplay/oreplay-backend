<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Results\Model\Table\RawUploadsTable;
use Results\Test\Fixture\RawUploadsFixture;

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
}
