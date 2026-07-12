<?php

declare(strict_types = 1);

namespace Rankings\Test\Model\Table;

use Cake\TestSuite\TestCase;
use Rankings\Model\Entity\RankingOrganizer;
use Rankings\Model\Table\RankingOrganizersTable;
use Rankings\Test\Fixture\RankingOrganizersFixture;
use RestApi\Lib\Validator\ValidationException;

class RankingOrganizersTableTest extends TestCase
{
    protected array $fixtures = [
        RankingOrganizersFixture::LOAD,
    ];

    private RankingOrganizersTable $RankingOrganizers;

    public function setUp(): void
    {
        parent::setUp();
        $this->RankingOrganizers = RankingOrganizersTable::load();
    }

    public function testSaveAndRead(): void
    {
        $stageOrderId = '0198b1f0-2222-7000-8000-000000000002';
        $organizer = $this->RankingOrganizers->patchFromNewWithUuid([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'stage_order_id' => $stageOrderId,
        ]);
        /** @var RankingOrganizer $saved */
        $saved = $this->RankingOrganizers->saveOrFail($organizer);

        $read = $this->RankingOrganizers->get($saved->id);
        $this->assertSame('Ada', $read->first_name);
        $this->assertSame('Lovelace', $read->last_name);
        $this->assertSame($stageOrderId, $read->stage_order_id);
        $this->assertNull($read->runner_id);
        $this->assertNotEmpty($read->created);
    }

    public function testRunnerIdIsOptional(): void
    {
        $organizer = $this->RankingOrganizers->patchFromNewWithUuid([
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'runner_id' => '0198b1f0-3333-7000-8000-000000000003',
            'stage_order_id' => '0198b1f0-2222-7000-8000-000000000002',
        ]);
        $saved = $this->RankingOrganizers->saveOrFail($organizer);
        $this->assertSame('0198b1f0-3333-7000-8000-000000000003', $saved->runner_id);
    }

    public function testFirstNameIsRequired(): void
    {
        $this->expectException(ValidationException::class);
        $this->RankingOrganizers->patchFromNewWithUuid([
            'last_name' => 'NoFirstName',
            'stage_order_id' => '0198b1f0-2222-7000-8000-000000000002',
        ]);
    }

    public function testStageOrderIdIsRequired(): void
    {
        $this->expectException(ValidationException::class);
        $this->RankingOrganizers->patchFromNewWithUuid([
            'first_name' => 'No',
            'last_name' => 'StageOrder',
        ]);
    }
}
