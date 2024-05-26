<?php

declare(strict_types = 1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Event;
use Results\Model\Entity\Runner;
use Results\Model\Entity\Stage;

class RunnersFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Results.Runners';

    public const RUNNER_RAID_ID = '3c3b3cb5-1b86-491f-a6ef-f7d12e3d41b7';
    public const RUNNER_UUID = '7232f069-a361-474a-9ec4-5c51c2b4407e';

    public $records = [
        [
            'id' => Runner::FIRST_RUNNER,
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => Stage::FIRST_STAGE,
            'uuid' => self::RUNNER_UUID,
            'first_name' => 'First',
            'last_name' => 'Runner',
            'db_id' => null,
            'iof_id' => null,
            'bib_number' => 4444,
            'bib_alt' => null,
            'sicard' => 2009933,
            'sicard_alt' => null,
            'license' => null,
            'national_id' => null,
            'birth_date' => null,
            'sex' => null,
            'telephone1' => null,
            'telephone2' => null,
            'email' => null,
            'user_id' => null,
            'class_id' => ClassEntity::ME,
            'class_uuid' => null,
            'club_id' => ClubsFixture::CLUB_1,
            'team_id' => null,
            'leg_number' => null,
            'created' => '2024-01-02 10:00:05',
            'modified' => '2024-01-02 10:00:05',
            'deleted' => null,
        ],
        [
            'id' => self::RUNNER_RAID_ID,
            'event_id' => EventsFixture::FIRST_RAID,
            'stage_id' => StagesFixture::STAGE_RAID,
            'uuid' => self::RUNNER_UUID,
            'first_name' => 'Second',
            'last_name' => 'Raider',
            'db_id' => null,
            'iof_id' => null,
            'bib_number' => 4444,
            'bib_alt' => null,
            'sicard' => null,
            'sicard_alt' => null,
            'license' => null,
            'national_id' => null,
            'birth_date' => null,
            'sex' => null,
            'telephone1' => null,
            'telephone2' => null,
            'email' => null,
            'user_id' => null,
            'class_id' => null,
            'class_uuid' => null,
            'club_id' => ClubsFixture::CLUB_1,
            'team_id' => null,
            'leg_number' => null,
            'created' => '2024-01-03 10:00:05',
            'modified' => '2024-01-03 10:00:05',
            'deleted' => null,
        ],
    ];
}
