<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller\UploadExamples;

use Results\Lib\Consts\StatusCode;
use Results\Model\Entity\Event;
use Results\Model\Entity\ResultType;
use Results\Test\Fixture\StagesFixture;

class TotalsExamples
{
    public static function simpleTotalPoints($timeSeconds = 2931)
    {
        return [
            'configuration' => [
                'cSeparator' => ';',
                'cEncoding' => 'ISO-8859-1',
                'file' => '/OReplayExamples/12totalizadores/tmp/TOTALES.csv',
                'extension' => 'CSV',
                'utf' => false,
                'known_data' => true,
                'contents' => 'ResultList',
                'results_type' => 'Totals',
                'one_stage' => false,
                'source' => 'OEv12',
                'iof_version' => 'Other',
                'include_score' => false,
                'trailo_type' => 'Other',
                'trailo_at' => 'Other',
                'trailo_normal' => '0',
                'trailo_group' => '0',
                'totalization' => 'TotalizationPoints'
            ],
            'event' => [
                'id' => Event::FIRST_EVENT,
                'description' => 'Totals',
                'is_hidden' => false,
                'stages' => [
                    (int) 0 => [
                        'id' => StagesFixture::STAGE_FEDO_2,
                        'description' => 'puntos',
                        'classes' => [
                            (int) 0 => [
                                'id' => '',
                                'uuid' => '',
                                'oe_key' => '14',
                                'short_name' => 'F-E',
                                'long_name' => 'F-E',
                                'runners' => [
                                    (int) 0 => [
                                        'id' => '',
                                        'uuid' => '',
                                        'sicard' => '8653333',
                                        'sex' => 'F',
                                        'email' => '',
                                        'first_name' => 'Nerea',
                                        'last_name' => 'Pita',
                                        'db_id' => '8331',
                                        'iof_id' => '33642',
                                        'bib_number' => '4',
                                        'bib_alt' => '2876',
                                        'sicard_alt' => '',
                                        'runner_results' => [
                                            (int) 0 => [
                                                'id' => '',
                                                'position' => (int) 1,
                                                'stage_order' => (int) 1,
                                                'time_seconds' => (int) $timeSeconds,
                                                'time_behind' => (int) 140,
                                                'time_neutralization' => (int) 0,
                                                'time_adjusted' => (int) 0,
                                                'time_penalty' => (int) 0,
                                                'time_bonus' => (int) 0,
                                                'points_final' => (int) 1856,
                                                'points_adjusted' => (int) 0,
                                                'points_penalty' => (int) 0,
                                                'points_bonus' => (int) 0,
                                                'leg_number' => (int) 1,
                                                'result_type' => [
                                                    'id' => ResultType::OVERALL,
                                                    'description' => 'Overall'
                                                ]
                                            ],
                                            (int) 1 => [
                                                'id' => '',
                                                'position' => (int) 4,
                                                'stage_order' => (int) 1,
                                                'time_seconds' => (int) 2083,
                                                'status_code' => '0',
                                                'time_behind' => (int) 275,
                                                'time_neutralization' => (int) 0,
                                                'time_adjusted' => (int) 0,
                                                'time_penalty' => (int) 0,
                                                'time_bonus' => (int) 0,
                                                'points_final' => (int) 868,
                                                'points_adjusted' => (int) 0,
                                                'points_penalty' => (int) 0,
                                                'points_bonus' => (int) 0,
                                                'leg_number' => (int) 1,
                                                'result_type' => [
                                                    'id' => ResultType::STAGE,
                                                    'description' => 'Stage'
                                                ]
                                            ],
                                            (int) 2 => [
                                                'id' => '',
                                                'position' => (int) 2,
                                                'stage_order' => (int) 2,
                                                'time_seconds' => (int) 848,
                                                'status_code' => '0',
                                                'time_behind' => (int) 10,
                                                'time_neutralization' => (int) 0,
                                                'time_adjusted' => (int) 0,
                                                'time_penalty' => (int) 0,
                                                'time_bonus' => (int) 0,
                                                'points_final' => (int) 988,
                                                'points_adjusted' => (int) 0,
                                                'points_penalty' => (int) 0,
                                                'points_bonus' => (int) 0,
                                                'leg_number' => (int) 1,
                                                'result_type' => [
                                                    'id' => ResultType::PARTIAL_OVERALL,
                                                ]
                                            ]
                                        ],
                                        'club' => [
                                            'id' => '',
                                            'uuid' => '',
                                            'city' => 'Toledo',
                                            'oe_key' => '68',
                                            'short_name' => 'TOLEDO-O',
                                            'long_name' => 'TOLEDO-O'
                                        ]
                                    ],
                                    (int) 1 => [
                                        'id' => '',
                                        'uuid' => '',
                                        'sicard' => '8650888',
                                        'sex' => 'F',
                                        'email' => '',
                                        'first_name' => 'Marta',
                                        'last_name' => 'Alonso',
                                        'db_id' => '3501',
                                        'iof_id' => '20222',
                                        'bib_number' => '6',
                                        'bib_alt' => '3981',
                                        'sicard_alt' => '',
                                        'runner_results' => [
                                            (int) 0 => [
                                                'id' => '',
                                                'position' => (int) 2,
                                                'stage_order' => (int) 1,
                                                'time_seconds' => (int) 2791,
                                                'time_behind' => (int) 0,
                                                'time_neutralization' => (int) 0,
                                                'time_adjusted' => (int) 0,
                                                'time_penalty' => (int) 0,
                                                'time_bonus' => (int) 0,
                                                'points_final' => (int) 1852,
                                                'points_adjusted' => (int) 0,
                                                'points_penalty' => (int) 0,
                                                'points_bonus' => (int) 0,
                                                'leg_number' => (int) 1,
                                                'result_type' => [
                                                    'id' => ResultType::OVERALL,
                                                    'description' => 'Overall'
                                                ]
                                            ],
                                            (int) 1 => [
                                                'id' => '',
                                                'position' => (int) 1,
                                                'stage_order' => (int) 1,
                                                'time_seconds' => (int) 1808,
                                                'status_code' => '0',
                                                'time_behind' => (int) 0,
                                                'time_neutralization' => (int) 0,
                                                'time_adjusted' => (int) 0,
                                                'time_penalty' => (int) 0,
                                                'time_bonus' => (int) 0,
                                                'points_final' => (int) 1000,
                                                'points_adjusted' => (int) 0,
                                                'points_penalty' => (int) 0,
                                                'points_bonus' => (int) 0,
                                                'leg_number' => (int) 1,
                                                'result_type' => [
                                                    'id' => ResultType::PARTIAL_OVERALL,
                                                ]
                                            ],
                                            (int) 2 => [
                                                'id' => '',
                                                'position' => (int) 5,
                                                'stage_order' => (int) 2,
                                                'time_seconds' => (int) 983,
                                                'status_code' => '0',
                                                'time_behind' => (int) 145,
                                                'time_neutralization' => (int) 0,
                                                'time_adjusted' => (int) 0,
                                                'time_penalty' => (int) 0,
                                                'time_bonus' => (int) 0,
                                                'points_final' => (int) 852,
                                                'points_adjusted' => (int) 0,
                                                'points_penalty' => (int) 0,
                                                'points_bonus' => (int) 0,
                                                'leg_number' => (int) 1,
                                                'result_type' => [
                                                    'id' => ResultType::PARTIAL_OVERALL,
                                                ]
                                            ]
                                        ],
                                        'club' => [
                                            'id' => '',
                                            'uuid' => '',
                                            'city' => 'Cáceres',
                                            'oe_key' => '117',
                                            'short_name' => 'VIA_PLATA',
                                            'long_name' => 'VIA_PLATA'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    public static function stage1RealTotalPoints(): array
    {
        $runner1 = [
            'id' => '',
            'uuid' => '',
            'sicard' => '8664000',
            'sex' => 'M',
            'telephone1' => '',
            'telephone2' => '',
            'email' => '',
            'first_name' => 'Paco',
            'last_name' => 'Fernandez',
            'db_id' => '11600',
            'iof_id' => '',
            'bib_number' => '2800',
            'bib_alt' => '',
            'sicard_alt' => '',
            'is_nc' => false,
            'runner_results' => [
                (int) 0 => [
                    'id' => '',
                    'position' => (int) 1,
                    'contributory' => false,
                    'stage_order' => (int) 1,
                    'time_seconds' => (int) 2053,
                    'time_behind' => (int) 0,
                    'time_neutralization' => (int) 0,
                    'time_adjusted' => (int) 0,
                    'time_penalty' => (int) 0,
                    'time_bonus' => (int) 0,
                    'points_final' => (int) 1000,
                    'points_adjusted' => (int) 0,
                    'points_penalty' => (int) 0,
                    'points_bonus' => (int) 0,
                    'leg_number' => (int) 1,
                    'result_type' => [
                        'id' => '5542d38b-8bd3-40f4-913d-2c38048a0b04',
                        'description' => 'Overall'
                    ]
                ],
                (int) 1 => [
                    'id' => '',
                    'position' => (int) 1,
                    'contributory' => true,
                    'stage_order' => (int) 1,
                    'time_seconds' => (int) 2053,
                    'status_code' => '0',
                    'time_behind' => (int) 0,
                    'time_neutralization' => (int) 0,
                    'time_adjusted' => (int) 0,
                    'time_penalty' => (int) 0,
                    'time_bonus' => (int) 0,
                    'points_final' => (int) 1000,
                    'points_adjusted' => (int) 0,
                    'points_penalty' => (int) 0,
                    'points_bonus' => (int) 0,
                    'leg_number' => (int) 1,
                    'result_type' => [
                        'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                        'description' => 'Stage'
                    ]
                ]
            ],
            'club' => [
                'id' => '',
                'uuid' => '',
                'city' => 'Toledo',
                'oe_key' => '68',
                'short_name' => 'TOLEDO-O',
                'long_name' => 'TOLEDO-O'
            ]
        ];
        $runner2 = [
            'id' => '',
            'uuid' => '',
            'sicard' => '8650855',
            'sex' => 'M',
            'telephone1' => '',
            'telephone2' => '682404888',
            'email' => '',
            'first_name' => 'Elmar',
            'last_name' => 'Martinez',
            'db_id' => '8888',
            'iof_id' => '',
            'bib_number' => '3399',
            'bib_alt' => '',
            'sicard_alt' => '',
            'is_nc' => false,
            'runner_results' => [
                (int) 0 => [
                    'id' => '',
                    'position' => (int) 2,
                    'contributory' => false,
                    'stage_order' => (int) 1,
                    'time_seconds' => (int) 2138,
                    'time_behind' => (int) 85,
                    'time_neutralization' => (int) 0,
                    'time_adjusted' => (int) 0,
                    'time_penalty' => (int) 0,
                    'time_bonus' => (int) 0,
                    'points_final' => (int) 960,
                    'points_adjusted' => (int) 0,
                    'points_penalty' => (int) 0,
                    'points_bonus' => (int) 0,
                    'leg_number' => (int) 1,
                    'result_type' => [
                        'id' => '5542d38b-8bd3-40f4-913d-2c38048a0b04',
                        'description' => 'Overall'
                    ]
                ],
                (int) 1 => [
                    'id' => '',
                    'position' => (int) 2,
                    'contributory' => true,
                    'stage_order' => (int) 1,
                    'time_seconds' => (int) 2138,
                    'status_code' => '0',
                    'time_behind' => (int) 85,
                    'time_neutralization' => (int) 0,
                    'time_adjusted' => (int) 0,
                    'time_penalty' => (int) 0,
                    'time_bonus' => (int) 0,
                    'points_final' => (int) 960,
                    'points_adjusted' => (int) 0,
                    'points_penalty' => (int) 0,
                    'points_bonus' => (int) 0,
                    'leg_number' => (int) 1,
                    'result_type' => [
                        'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                        'description' => 'Stage'
                    ]
                ]
            ],
            'club' => [
                'id' => '',
                'uuid' => '',
                'city' => 'Girona',
                'oe_key' => '6',
                'short_name' => 'ALIGOTS',
                'long_name' => 'ALIGOTS'
            ]
        ];
        return [
            'configuration' => [
                'cSeparator' => ';',
                'cEncoding' => 'ISO-8859-1',
                'file' => 'tmp/E1 puntos CSV.csv',
                'extension' => 'CSV',
                'utf' => false,
                'known_data' => true,
                'contents' => 'ResultList',
                'results_type' => 'Totals',
                'one_stage' => false,
                'source' => 'OEv12',
                'iof_version' => 'Other',
                'include_score' => true,
                'trailo_type' => 'Other',
                'trailo_at' => 'Other',
                'trailo_normal' => '0',
                'trailo_group' => '0',
                'totalization' => 'TotalizationPoints'
            ],
            'event' => [
                'id' => Event::FIRST_EVENT,
                'description' => 'Totals test',
                'is_hidden' => false,
                'stages' => [
                    (int) 0 => [
                        'id' => StagesFixture::STAGE_FEDO_2,
                        'description' => '04_20251005_111937098_posta3',
                        'base_date' => '2025-09-09',
                        'base_time' => '10:00:00.000+02:00',
                        'order_number' => (int) 1,
                        'classes' => [
                            (int) 0 => [
                                'id' => '',
                                'uuid' => '',
                                'oe_key' => '2',
                                'short_name' => 'M-E',
                                'long_name' => 'M-E',
                                'runners' => [
                                    $runner1,
                                    //$runner2,
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    public static function stage2RealTotalPoints(): array
    {
        $runner1 = [
            'id' => '',
            'uuid' => '',
            'sicard' => '8664000',
            'sex' => 'M',
            'telephone1' => '',
            'telephone2' => '',
            'email' => '',
            'first_name' => 'Paco',
            'last_name' => 'Fernandez',
            'db_id' => '11600',
            'iof_id' => '',
            'bib_number' => '2800',
            'bib_alt' => '',
            'sicard_alt' => '',
            'is_nc' => false,
            'runner_results' => [
                (int) 0 => [
                    'id' => '',
                    '_test_note' => 'Paco 1',
                    'position' => (int) 1,
                    'contributory' => false,
                    'stage_order' => (int) 1,
                    'time_seconds' => (int) 2937,
                    'time_behind' => (int) 0,
                    'time_neutralization' => (int) 0,
                    'time_adjusted' => (int) 0,
                    'time_penalty' => (int) 0,
                    'time_bonus' => (int) 0,
                    'points_final' => (int) 2000,
                    'points_adjusted' => (int) 0,
                    'points_penalty' => (int) 0,
                    'points_bonus' => (int) 0,
                    'leg_number' => (int) 1,
                    'result_type' => [
                        'id' => '5542d38b-8bd3-40f4-913d-2c38048a0b04',
                        'description' => 'Overall'
                    ]
                ],
                (int) 1 => [
                    'id' => '',
                    '_test_note' => 'Paco 2',
                    'position' => (int) 1,
                    'contributory' => true,
                    'stage_order' => (int) 1,
                    'time_seconds' => (int) 2053,
                    'status_code' => '0',
                    'time_behind' => (int) 0,
                    'time_neutralization' => (int) 0,
                    'time_adjusted' => (int) 0,
                    'time_penalty' => (int) 0,
                    'time_bonus' => (int) 0,
                    'points_final' => (int) 1000,
                    'points_adjusted' => (int) 0,
                    'points_penalty' => (int) 0,
                    'points_bonus' => (int) 0,
                    'leg_number' => (int) 1,
                    'result_type' => [
                        'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                        'description' => 'Stage'
                    ]
                ],
                (int) 2 => [
                    'id' => '',
                    '_test_note' => 'Paco 3',
                    'position' => (int) 1,
                    'contributory' => true,
                    'stage_order' => (int) 2,
                    'time_seconds' => (int) 884,
                    'status_code' => '0',
                    'time_behind' => (int) 0,
                    'time_neutralization' => (int) 0,
                    'time_adjusted' => (int) 0,
                    'time_penalty' => (int) 0,
                    'time_bonus' => (int) 0,
                    'points_final' => (int) 1000,
                    'points_adjusted' => (int) 0,
                    'points_penalty' => (int) 0,
                    'points_bonus' => (int) 0,
                    'leg_number' => (int) 1,
                    'result_type' => [
                        'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                        'description' => 'Stage'
                    ]
                ]
            ],
            'club' => [
                'id' => '',
                'uuid' => '',
                'city' => 'Toledo',
                'oe_key' => '68',
                'short_name' => 'TOLEDO-O',
                'long_name' => 'TOLEDO-O'
            ]
        ];
        $runner2 = [
            'id' => '',
            'uuid' => '',
            'sicard' => '8650855',
            'sex' => 'M',
            'telephone1' => '',
            'telephone2' => '682404888',
            'email' => '',
            'first_name' => 'Elmar',
            'last_name' => 'Martinez',
            'db_id' => '8888',
            'iof_id' => '',
            'bib_number' => '3399',
            'bib_alt' => '',
            'sicard_alt' => '',
            'is_nc' => false,
            'runner_results' => [
                (int) 0 => [
                    'id' => '',
                    '_test_note' => 'Elmar 1',
                    'position' => (int) 2,
                    'contributory' => false,
                    'stage_order' => (int) 1,
                    'time_seconds' => (int) 3100,
                    'time_behind' => (int) 163,
                    'time_neutralization' => (int) 0,
                    'time_adjusted' => (int) 0,
                    'time_penalty' => (int) 0,
                    'time_bonus' => (int) 0,
                    'points_final' => (int) 1880,
                    'points_adjusted' => (int) 0,
                    'points_penalty' => (int) 0,
                    'points_bonus' => (int) 0,
                    'leg_number' => (int) 1,
                    'result_type' => [
                        'id' => '5542d38b-8bd3-40f4-913d-2c38048a0b04',
                        'description' => 'Overall'
                    ]
                ],
                (int) 1 => [
                    'id' => '',
                    '_test_note' => 'Elmar 2',
                    'position' => (int) 2,
                    'contributory' => true,
                    'stage_order' => (int) 1,
                    'time_seconds' => (int) 2138,
                    'status_code' => '0',
                    'time_behind' => (int) 85,
                    'time_neutralization' => (int) 0,
                    'time_adjusted' => (int) 0,
                    'time_penalty' => (int) 0,
                    'time_bonus' => (int) 0,
                    'points_final' => (int) 960,
                    'points_adjusted' => (int) 0,
                    'points_penalty' => (int) 0,
                    'points_bonus' => (int) 0,
                    'leg_number' => (int) 1,
                    'result_type' => [
                        'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                        'description' => 'Stage'
                    ]
                ],
                (int) 2 => [
                    'id' => '',
                    '_test_note' => 'Elmar 3',
                    'position' => (int) 3,
                    'contributory' => true,
                    'stage_order' => (int) 2,
                    'time_seconds' => (int) 961,
                    'status_code' => '0',
                    'time_behind' => (int) 77,
                    'time_neutralization' => (int) 0,
                    'time_adjusted' => (int) 0,
                    'time_penalty' => (int) 0,
                    'time_bonus' => (int) 0,
                    'points_final' => (int) 920,
                    'points_adjusted' => (int) 0,
                    'points_penalty' => (int) 0,
                    'points_bonus' => (int) 0,
                    'leg_number' => (int) 1,
                    'result_type' => [
                        'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                        'description' => 'Stage'
                    ]
                ]
            ],
            'club' => [
                'id' => '',
                'uuid' => '',
                'city' => 'Girona',
                'oe_key' => '6',
                'short_name' => 'ALIGOTS',
                'long_name' => 'ALIGOTS'
            ]
        ];
        return [
            'configuration' => [
                'cSeparator' => ';',
                'cEncoding' => 'ISO-8859-1',
                'file' => 'tmp/E2 puntos CSV.csv',
                'extension' => 'CSV',
                'utf' => false,
                'known_data' => true,
                'contents' => 'ResultList',
                'results_type' => 'Totals',
                'one_stage' => false,
                'source' => 'OEv12',
                'iof_version' => 'Other',
                'include_score' => true,
                'trailo_type' => 'Other',
                'trailo_at' => 'Other',
                'trailo_normal' => '0',
                'trailo_group' => '0',
                'totalization' => 'TotalizationPoints'
            ],
            'event' => [
                'id' => Event::FIRST_EVENT,
                'description' => 'Totals test',
                'is_hidden' => false,
                'stages' => [
                    (int) 0 => [
                        'id' => StagesFixture::STAGE_FEDO_2,
                        'description' => '04_20251005_111937098_posta3',
                        'base_date' => '2025-09-09',
                        'base_time' => '10:00:00.000+02:00',
                        'order_number' => (int) 1,
                        'classes' => [
                            (int) 0 => [
                                'id' => '',
                                'uuid' => '',
                                'oe_key' => '2',
                                'short_name' => 'M-E',
                                'long_name' => 'M-E',
                                'runners' => [
                                    $runner1,
                                    //$runner2,
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
