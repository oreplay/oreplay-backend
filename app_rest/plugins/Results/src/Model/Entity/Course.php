<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

/**
 * @property RunnerResult[] $runner_results
 * @property TeamResult[] $team_results
 * @property string $first_name
 * @property string $last_name
 * @property Club $club
 * @property string $event_id
 * @property string $stage_id
 * @property mixed $sicard
 * @property mixed $bib_number
 */
class Course extends AppEntity
{
    protected $_accessible = [
        '*' => false,
        'id' => false,
        'short_name' => true,
        'oe_key' => true,
        'distance' => true,
        'climb' => true,
        'controls' => true,
    ];

    protected $_virtual = [
    ];

    protected $_hidden = [
        'event_id',
        'stage_id',
        'uuid',
        'long_name',
        'coord_system',
        'datum',
        'utm_zone',
        'hemisphere',
        'latitude',
        'longitude',
        'zoom',
        'created',
        'modified',
        'deleted',
    ];
}
