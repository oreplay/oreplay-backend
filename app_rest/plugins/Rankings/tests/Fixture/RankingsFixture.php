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

    public $records = [
        [
            'id' => RankingsTable::FIRST_RANKING,
            'scoring_algorithm' => SimpleScoreCalculator::class,
            'event_id' => EventsFixture::EVENT_TOMORROW_RANKING,
            'stage_id' => StagesFixture::STAGE_RANKING,
            'max_points' => 100,
            'round_precision' => Ranking::USE_FLOOR_INSTEAD_OF_ROUND,
            'nc_true' => 0,
            'nc_false' => null,
            'status_scores' => '[null,0,10,10,0,10]',
            'excluded_class_names' => '["O NEGRO F","PROM"]',
            // phpcs:ignore
            'overall_settings' => '{"totalCircuitRaces":9,"maxRacesCounted":5,"organizerScoringFraction":0.3,"minPointsAsOrg":50}',
            'created' => '2024-01-02 10:00:18',
            'modified' => '2024-01-02 10:00:18',
            'deleted' => null,
        ],
    ];
}
