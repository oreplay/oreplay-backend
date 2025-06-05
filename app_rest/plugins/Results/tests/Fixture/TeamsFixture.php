<?php

declare(strict_types = 1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Model\Entity\Team;

class TeamsFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Results.Teams';

    public $records = [
        [
            'id' => Team::FIRST_TEAM,
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => Stage::FIRST_STAGE,
            'uuid' => '',
            'bib_number' => '301',
            'bib_alt' => '',
            'is_nc' => false,
            'eligibility' => '',
            'team_name' => 'First Team',
            'sicard' => '',
            'sicard_alt' => '',
            'class_id' => ClassEntity::ME,
            'class_uuid' => '',
            'club_id' => ClubsFixture::CLUB_1,
            'legs' => '',
            'created' => '2024-01-03 10:00:06',
            'modified' => '2024-01-03 10:00:06',
            'deleted' => null,
        ]
    ];
}
