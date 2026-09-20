<?php

declare(strict_types = 1);

namespace Rankings\Test\Fixture;

use Rankings\Lib\ScoringAlgorithms\SimpleScoreCalculator;
use Rankings\Model\Entity\Ranking;
use Rankings\Model\Table\RankingsTable;
use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\StagesFixture;

class RankingsFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Rankings.Rankings';

    public array $records = [
        [
            'id' => RankingsTable::FIRST_RANKING,
            'scoring_algorithm' => SimpleScoreCalculator::class,
            'event_id' => EventsFixture::EVENT_TOMORROW_RANKING,
            'stage_id' => StagesFixture::STAGE_RANKING,
            'title' => 'Regional ranking 100pts',
            'max_points' => 100,
            'round_precision' => Ranking::USE_FLOOR_INSTEAD_OF_ROUND,
            'nc_true' => 0,
            'nc_false' => null,
            'status_scores' => '[null,0,10,10,0,10]',
            'excluded_class_names' => '["O NEGRO F","PROM"]',
            'included_class_names' => '['
                // Foot-o
                // phpcs:ignore
                . '"M-12","F-12","M-14","F-14","M-16","F-16","M-18","F-18","SENIOR","F-SENIOR", "M-35","F-35","M-45","F-45","M-55","F-55","M-65","F-65",'
                // MTBO
                // phpcs:ignore
                . '"M-15","F-15","M-17","F-17","M-20","F-20","E","F-E","M-21","F-21", "M-40","F-40","M-50","F-50","M-60","F-60","Absoluta parellas","E-bike",'
                // Tests
                . '"ME","FE"]',
            // phpcs:ignore
            'overall_settings' => '{"totalCircuitRaces":9,"maxRacesCounted":5,"organizerScoringFraction":0.3,"minPointsAsOrg":50}',
            'created' => '2024-01-02 10:00:18',
            'modified' => '2024-01-02 10:00:18',
            'deleted' => null,
        ],
    ];
}
