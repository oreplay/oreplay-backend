<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Model\Entity\UploadLog;
use Results\Model\Table\UploadLogsTable;
use Results\Test\Fixture\UploadLogsFixture;

class UploadLogsTableTest extends TestCase
{
    protected array $fixtures = [
        UploadLogsFixture::LOAD,
    ];
    private UploadLogsTable $UploadLogs;

    public function setUp(): void
    {
        parent::setUp();
        $this->UploadLogs = UploadLogsTable::load();
    }

    public function testSaveStateEnded(): void
    {
        $saved = $this->UploadLogs->saveStateEnded(Event::FIRST_EVENT, Stage::FIRST_STAGE);
        $this->assertEquals(UploadLog::STATE_ENDED, $saved->state);
        $this->assertEquals(1, $this->UploadLogs->find()->where(['state' => UploadLog::STATE_ENDED])->all()->count());
    }

    public function testDeleteStateEnded(): void
    {
        $this->testSaveStateEnded();

        $saved = $this->UploadLogs->deleteStateEnded(Event::FIRST_EVENT, Stage::FIRST_STAGE);
        $this->assertEquals(1, $saved);
        $this->assertEquals(0, $this->UploadLogs->find()->where(['state' => UploadLog::STATE_ENDED])->all()->count());
    }
}
