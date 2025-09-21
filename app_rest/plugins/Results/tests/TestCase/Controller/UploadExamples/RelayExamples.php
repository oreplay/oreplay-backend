<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller\UploadExamples;

use Results\Lib\Consts\StatusCode;
use Results\Model\Entity\Event;
use Results\Model\Entity\ResultType;
use Results\Test\Fixture\StagesFixture;

class RelayExamples
{
    public static function simple3relay()
    {
        $runnerA = [
            'id' => '',
            'uuid' => '',
            'sicard' => '8186666',
            'sex' => 'F',
            'first_name' => 'María',
            'last_name' => 'Prado',
            'db_id' => '6208',
            'iof_id' => '',
            'bib_number' => '1001-1',
            'sicard_alt' => '',
            //'leg_number' => (int) 1,
            'runner_results' => [
                (int) 0 => [
                    'id' => '',
                    'start_time' => '2001-01-01T10:35:00.000+01:00',
                    'finish_time' => '2001-01-01T11:04:04.000+01:00',
                    'time_seconds' => (int) 1744,
                    'status_code' => '0',
                    'time_neutralization' => (int) 0,
                    'time_adjusted' => (int) 0,
                    'time_penalty' => (int) 0,
                    'time_bonus' => (int) 0,
                    'points_final' => (int) 0,
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
                'city' => '',
                'oe_key' => '12',
                'short_name' => 'Galicia',
                'long_name' => 'Galicia'
            ]
        ];
        $runnerB = [
            'id' => '',
            'uuid' => '',
            'sicard' => '8664444',
            'sex' => 'F',
            'first_name' => 'Ines',
            'last_name' => 'Pardo',
            'db_id' => '11323',
            'iof_id' => '',
            'bib_number' => '1001-2',
            'sicard_alt' => '',
            //'leg_number' => (int) 2,
            'runner_results' => [
                (int) 0 => [
                    'id' => '',
                    'start_time' => '2001-01-01T11:04:04.000+01:00',
                    'finish_time' => '2001-01-01T11:42:42.000+01:00',
                    'time_seconds' => (int) 2318,
                    'status_code' => '0',
                    'time_neutralization' => (int) 0,
                    'time_adjusted' => (int) 0,
                    'time_penalty' => (int) 0,
                    'time_bonus' => (int) 0,
                    'points_final' => (int) 0,
                    'points_adjusted' => (int) 0,
                    'points_penalty' => (int) 0,
                    'points_bonus' => (int) 0,
                    'leg_number' => (int) 2,
                    'result_type' => [
                        'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                        'description' => 'Stage'
                    ]
                ]
            ],
            'club' => [
                'id' => '',
                'uuid' => '',
                'city' => '',
                'oe_key' => '12',
                'short_name' => 'Galicia',
                'long_name' => 'Galicia'
            ]
        ];
        $runnerC = [
            'id' => '',
            'uuid' => '',
            'sicard' => '8655555',
            'sex' => 'F',
            'first_name' => 'Ana',
            'last_name' => 'Torres',
            'db_id' => '8515',
            'iof_id' => '',
            'bib_number' => '1001-3',
            'sicard_alt' => '',
            'leg_number' => (int) 3,
            'runner_results' => [
                (int) 0 => [
                    'id' => '',
                    'start_time' => '2001-01-01T11:42:42.000+01:00',
                    'finish_time' => '2001-01-01T12:09:42.000+01:00',
                    'time_seconds' => (int) 1619,
                    'status_code' => '0',
                    'time_neutralization' => (int) 0,
                    'time_adjusted' => (int) 0,
                    'time_penalty' => (int) 0,
                    'time_bonus' => (int) 0,
                    'points_final' => (int) 0,
                    'points_adjusted' => (int) 0,
                    'points_penalty' => (int) 0,
                    'points_bonus' => (int) 0,
                    'leg_number' => (int) 3,
                    'result_type' => [
                        'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                        'description' => 'Stage'
                    ]
                ]
            ],
            'club' => [
                'id' => '',
                'uuid' => '',
                'city' => '',
                'oe_key' => '12',
                'short_name' => 'Galicia',
                'long_name' => 'Galicia'
            ]
        ];
        $team = [
            'id' => '',
            'uuid' => '',
            'legs' => (int) 3,
            'bib_number' => '1001',
            'bib_alt' => '',
            'team_name' => 'CMA-SENIOR FEM-01',
            'club' => [
                'id' => '',
                'uuid' => '',
                'city' => '',
                'oe_key' => '12',
                'short_name' => 'Galicia',
                'long_name' => 'Galicia'
            ],
            'team_results' => [
                [
                    'id' => '',
                    'position' => (int) 1,
                    'start_time' => '2001-01-01T10:35:00.000+01:00',
                    'time_seconds' => 1745,
                    'status_code' => '0',
                    'time_behind' => (int) 0,
                    'leg_number' => 1,
                    'result_type' => [
                        'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                        'description' => 'Stage'
                    ]
                ],
                [
                    // we have as many team results as legs in a relay
                    'id' => '',
                    'position' => 1,
                    'start_time' => '2001-01-01T10:35:00.000+01:00',
                    'time_seconds' => 2935,
                    'status_code' => '0',
                    'time_behind' => (int) 0,
                    'leg_number' => 2,
                    'result_type' => [
                        'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                        'description' => 'Stage'
                    ]
                ],
            ],
            'runners' => [
                $runnerA,
                $runnerB,
                $runnerC
            ]
        ];
        return [
            'configuration' => [
                'cSeparator' => ';',
                'cEncoding' => 'ISO-8859-1',
                'file' => '/tmp/OS12-relevos-provisionalesCategoriasFE.csv',
                'extension' => 'CSV',
                'utf' => false,
                'known_data' => true,
                'contents' => 'ResultList',
                'results_type' => 'Totals',
                'one_stage' => true,
                'source' => 'OSv12',
                'iof_version' => 'Other',
                'include_score' => false,
                'trailo_type' => 'Other',
                'trailo_at' => 'Other',
                'trailo_normal' => '0',
                'trailo_group' => '0',
                'totalization' => 'Other'
            ],
            'event' => [
                'id' => Event::FIRST_EVENT,
                'description' => 'Test back 0.2.25',
                'is_hidden' => false,
                'stages' => [
                    (int) 0 => [
                        'id' => StagesFixture::STAGE_FEDO_2,
                        'description' => 'Relevo Provisionales 0.2.25',
                        'classes' => [
                            (int) 0 => [
                                'id' => '',
                                'uuid' => '',
                                'oe_key' => '1',
                                'short_name' => 'SENIOR FEM',
                                'long_name' => 'SENIOR FEM',
                                'teams' => [$team]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    public static function twoTeamsWith2Runners4LegsEach()
    {
        $teamA = [
            'id' => '',
            'uuid' => '',
            'bib_number' => '102',
            'team_name' => 'Alicante ALICANTE-O AlicanteO-2',
            'club' => [
                'id' => '',
                'uuid' => '',
                'oe_key' => '257',
                'short_name' => 'Alicante ALICANTE-O',
                'long_name' => 'Alicante ALICANTE-O'
            ],
            'team_results' => [
                (int)0 => [
                    'id' => '',
                    'position' => (int)2,
                    'contributory' => true,
                    'stage_order' => (int)1,
                    'time_seconds' => (int)664,
                    'status_code' => '0',
                    'time_behind' => (int)25,
                    'time_neutralization' => (int)0,
                    'time_adjusted' => (int)0,
                    'time_penalty' => (int)0,
                    'time_bonus' => (int)0,
                    'leg_number' => (int)1,
                    'result_type' => [
                        'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                        'description' => 'Stage'
                    ]
                ],
                (int)1 => [
                    'id' => '',
                    'contributory' => true,
                    'stage_order' => (int)1,
                    'time_seconds' => (int)1444,
                    'status_code' => '0',
                    'time_neutralization' => (int)0,
                    'time_adjusted' => (int)0,
                    'time_penalty' => (int)0,
                    'time_bonus' => (int)0,
                    'leg_number' => (int)2,
                    'result_type' => [
                        'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                        'description' => 'Stage'
                    ]
                ],
                (int)2 => [
                    'id' => '',
                    'contributory' => true,
                    'stage_order' => (int)1,
                    'time_seconds' => (int)2896,
                    'status_code' => '0',
                    'time_neutralization' => (int)0,
                    'time_adjusted' => (int)0,
                    'time_penalty' => (int)0,
                    'time_bonus' => (int)0,
                    'leg_number' => (int)3,
                    'result_type' => [
                        'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                        'description' => 'Stage'
                    ]
                ],
                (int)3 => [
                    'id' => '',
                    'contributory' => true,
                    'stage_order' => (int)1,
                    'time_seconds' => (int)4159,
                    'status_code' => '0',
                    'time_neutralization' => (int)0,
                    'time_adjusted' => (int)0,
                    'time_penalty' => (int)0,
                    'time_bonus' => (int)0,
                    'leg_number' => (int)4,
                    'result_type' => [
                        'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                        'description' => 'Stage'
                    ]
                ]
            ],
            'runners' => [
                (int)0 => [
                    'id' => '',
                    'uuid' => '',
                    'sicard' => '1008699',
                    'sex' => 'F',
                    'first_name' => 'Cris',
                    'last_name' => 'Leiva',
                    'db_id' => '12834',
                    'sicard_alt' => '',
                    'is_nc' => false,
                    'runner_results' => [
                        (int)0 => [
                            'id' => '',
                            'contributory' => false,
                            'stage_order' => (int)1,
                            'start_time' => '2025-09-13T11:59:00.000+02:00',
                            'finish_time' => '2025-09-13T12:10:04.000+02:00',
                            'time_seconds' => (int)664,
                            'status_code' => '0',
                            'time_neutralization' => (int)0,
                            'time_adjusted' => (int)0,
                            'time_penalty' => (int)0,
                            'time_bonus' => (int)0,
                            'leg_number' => (int)1,
                            'result_type' => [
                                'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                                'description' => 'Stage'
                            ]
                        ]
                    ],
                    'club' => [
                        'id' => '',
                        'uuid' => '',
                        'oe_key' => '257',
                        'short_name' => 'Alicante ALICANTE-O',
                        'long_name' => 'Alicante ALICANTE-O'
                    ],
                    'course' => [
                        'id' => '',
                        'uuid' => '',
                        'distance' => '2100.0',
                        'climb' => '18.0',
                        'controls' => (int)18,
                        'oe_key' => '9004',
                        'short_name' => '#14 aBaB'
                    ]
                ],
                (int)1 => [
                    'id' => '',
                    'uuid' => '',
                    'sicard' => '8643979',
                    'sex' => 'M',
                    'first_name' => 'Violeta',
                    'last_name' => 'Rodriguez',
                    'db_id' => '28845',
                    'sicard_alt' => '',
                    'is_nc' => false,
                    'runner_results' => [
                        (int)0 => [
                            'id' => '',
                            'contributory' => false,
                            'stage_order' => (int)1,
                            'start_time' => '2025-09-13T12:10:04.000+02:00',
                            'finish_time' => '2025-09-13T12:23:04.000+02:00',
                            'time_seconds' => (int)780,
                            'status_code' => '0',
                            'time_neutralization' => (int)0,
                            'time_adjusted' => (int)0,
                            'time_penalty' => (int)0,
                            'time_bonus' => (int)0,
                            'leg_number' => (int)2,
                            'result_type' => [
                                'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                                'description' => 'Stage'
                            ]
                        ]
                    ],
                    'club' => [
                        'id' => '',
                        'uuid' => '',
                        'oe_key' => '257',
                        'short_name' => 'Alicante ALICANTE-O',
                        'long_name' => 'Alicante ALICANTE-O'
                    ],
                    'course' => [
                        'id' => '',
                        'uuid' => '',
                        'distance' => '2650.0',
                        'climb' => '23.0',
                        'controls' => (int)19,
                        'oe_key' => '9004',
                        'short_name' => '#6 bBaB'
                    ]
                ],
                (int)2 => [
                    'id' => '',
                    'uuid' => '',
                    'sicard' => '1008699',
                    'sex' => 'F',
                    'first_name' => 'Cris',
                    'last_name' => 'Leiva',
                    'db_id' => '12834',
                    'sicard_alt' => '',
                    'is_nc' => false,
                    'runner_results' => [
                        (int)0 => [
                            'id' => '',
                            'contributory' => false,
                            'stage_order' => (int)1,
                            'start_time' => '2025-09-13T12:23:04.000+02:00',
                            'finish_time' => '2025-09-13T12:47:16.000+02:00',
                            'time_seconds' => (int)1452,
                            'status_code' => '0',
                            'time_neutralization' => (int)0,
                            'time_adjusted' => (int)0,
                            'time_penalty' => (int)0,
                            'time_bonus' => (int)0,
                            'leg_number' => (int)3,
                            'result_type' => [
                                'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                                'description' => 'Stage'
                            ]
                        ]
                    ],
                    'club' => [
                        'id' => '',
                        'uuid' => '',
                        'oe_key' => '257',
                        'short_name' => 'Alicante ALICANTE-O',
                        'long_name' => 'Alicante ALICANTE-O'
                    ],
                    'course' => [
                        'id' => '',
                        'uuid' => '',
                        'distance' => '2575.0',
                        'climb' => '47.0',
                        'controls' => (int)22,
                        'oe_key' => '9004',
                        'short_name' => '#11 aAbA'
                    ]
                ],
                (int)3 => [
                    'id' => '',
                    'uuid' => '',
                    'sicard' => '8643979',
                    'sex' => 'M',
                    'first_name' => 'Violeta',
                    'last_name' => 'Rodriguez',
                    'db_id' => '28845',
                    'sicard_alt' => '',
                    'is_nc' => false,
                    'runner_results' => [
                        (int)0 => [
                            'id' => '',
                            'contributory' => false,
                            'stage_order' => (int)1,
                            'start_time' => '2025-09-13T12:47:16.000+02:00',
                            'finish_time' => '2025-09-13T13:08:19.000+02:00',
                            'time_seconds' => (int)1263,
                            'status_code' => '0',
                            'time_neutralization' => (int)0,
                            'time_adjusted' => (int)0,
                            'time_penalty' => (int)0,
                            'time_bonus' => (int)0,
                            'leg_number' => (int)4,
                            'result_type' => [
                                'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                                'description' => 'Stage'
                            ]
                        ]
                    ],
                    'club' => [
                        'id' => '',
                        'uuid' => '',
                        'oe_key' => '257',
                        'short_name' => 'Alicante ALICANTE-O',
                        'long_name' => 'Alicante ALICANTE-O'
                    ],
                    'course' => [
                        'id' => '',
                        'uuid' => '',
                        'distance' => '2875.0',
                        'climb' => '53.0',
                        'controls' => (int)25,
                        'oe_key' => '9004',
                        'short_name' => '#3 bAbA'
                    ]
                ]
            ]
        ];
        $teamB = [
            'id' => '',
            'uuid' => '',
            'bib_number' => '108',
            'team_name' => 'Murcia MURCIA-O GOE',
            'club' => [
                'id' => '',
                'uuid' => '',
                'oe_key' => '53',
                'short_name' => 'Murcia MURCIA-O',
                'long_name' => 'Murcia MURCIA-O'
            ],
            'team_results' => [
                (int)0 => [
                    'id' => '',
                    'contributory' => true,
                    'stage_order' => (int)1,
                    'status_code' => '9',
                    'time_neutralization' => (int)0,
                    'time_adjusted' => (int)0,
                    'time_penalty' => (int)0,
                    'time_bonus' => (int)0,
                    'leg_number' => (int)1,
                    'result_type' => [
                        'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                        'description' => 'Stage'
                    ]
                ],
                (int)1 => [
                    'id' => '',
                    'contributory' => true,
                    'stage_order' => (int)1,
                    'status_code' => '9',
                    'time_neutralization' => (int)0,
                    'time_adjusted' => (int)0,
                    'time_penalty' => (int)0,
                    'time_bonus' => (int)0,
                    'leg_number' => (int)2,
                    'result_type' => [
                        'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                        'description' => 'Stage'
                    ]
                ],
                (int)2 => [
                    'id' => '',
                    'contributory' => true,
                    'stage_order' => (int)1,
                    'status_code' => '9',
                    'time_neutralization' => (int)0,
                    'time_adjusted' => (int)0,
                    'time_penalty' => (int)0,
                    'time_bonus' => (int)0,
                    'leg_number' => (int)3,
                    'result_type' => [
                        'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                        'description' => 'Stage'
                    ]
                ],
                (int)3 => [
                    'id' => '',
                    'contributory' => true,
                    'stage_order' => (int)1,
                    'status_code' => '9',
                    'time_neutralization' => (int)0,
                    'time_adjusted' => (int)0,
                    'time_penalty' => (int)0,
                    'time_bonus' => (int)0,
                    'leg_number' => (int)4,
                    'result_type' => [
                        'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                        'description' => 'Stage'
                    ]
                ]
            ],
            'runners' => [
                (int)0 => [
                    'id' => '',
                    'uuid' => '',
                    'sicard' => '8197411',
                    'sex' => 'F',
                    'first_name' => 'Estrella',
                    'last_name' => 'Alonso',
                    'db_id' => '83389',
                    'sicard_alt' => '',
                    'is_nc' => false,
                    'runner_results' => [
                        (int)0 => [
                            'id' => '',
                            'contributory' => false,
                            'stage_order' => (int)1,
                            'start_time' => '2025-09-13T11:59:00.000+02:00',
                            'finish_time' => '2025-09-13T12:12:46.000+02:00',
                            'time_seconds' => (int)826,
                            'status_code' => '3',
                            'time_neutralization' => (int)0,
                            'time_adjusted' => (int)0,
                            'time_penalty' => (int)0,
                            'time_bonus' => (int)0,
                            'leg_number' => (int)1,
                            'result_type' => [
                                'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                                'description' => 'Stage'
                            ]
                        ]
                    ],
                    'club' => [
                        'id' => '',
                        'uuid' => '',
                        'oe_key' => '53',
                        'short_name' => 'Murcia MURCIA-O',
                        'long_name' => 'Murcia MURCIA-O'
                    ],
                    'course' => [
                        'id' => '',
                        'uuid' => '',
                        'distance' => '2125.0',
                        'climb' => '20.0',
                        'controls' => (int)18,
                        'oe_key' => '9004',
                        'short_name' => '#9 aAaA'
                    ]
                ],
                (int)1 => [
                    'id' => '',
                    'uuid' => '',
                    'sicard' => '8059555',
                    'sex' => 'M',
                    'first_name' => 'Cesar',
                    'last_name' => 'Gurrea',
                    'db_id' => '36956',
                    'sicard_alt' => '',
                    'is_nc' => false,
                    'runner_results' => [
                        (int)0 => [
                            'id' => '',
                            'contributory' => false,
                            'stage_order' => (int)1,
                            'start_time' => '2025-09-13T12:12:46.000+02:00',
                            'finish_time' => '2025-09-13T12:31:28.000+02:00',
                            'time_seconds' => (int)1122,
                            'status_code' => '0',
                            'time_neutralization' => (int)0,
                            'time_adjusted' => (int)0,
                            'time_penalty' => (int)0,
                            'time_bonus' => (int)0,
                            'leg_number' => (int)2,
                            'result_type' => [
                                'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                                'description' => 'Stage'
                            ]
                        ]
                    ],
                    'club' => [
                        'id' => '',
                        'uuid' => '',
                        'oe_key' => '53',
                        'short_name' => 'Murcia MURCIA-O',
                        'long_name' => 'Murcia MURCIA-O'
                    ],
                    'course' => [
                        'id' => '',
                        'uuid' => '',
                        'distance' => '2750.0',
                        'climb' => '23.0',
                        'controls' => (int)20,
                        'oe_key' => '9004',
                        'short_name' => '#2 bBaA'
                    ]
                ],
                (int)2 => [
                    'id' => '',
                    'uuid' => '',
                    'sicard' => '8197411',
                    'sex' => 'F',
                    'first_name' => 'Estrella',
                    'last_name' => 'Alonso',
                    'db_id' => '83389',
                    'sicard_alt' => '',
                    'is_nc' => false,
                    'runner_results' => [
                        (int)0 => [
                            'id' => '',
                            'contributory' => false,
                            'stage_order' => (int)1,
                            'start_time' => '2025-09-13T12:31:28.000+02:00',
                            'finish_time' => '2025-09-13T12:54:53.000+02:00',
                            'time_seconds' => (int)1405,
                            'status_code' => '0',
                            'time_neutralization' => (int)0,
                            'time_adjusted' => (int)0,
                            'time_penalty' => (int)0,
                            'time_bonus' => (int)0,
                            'leg_number' => (int)3,
                            'result_type' => [
                                'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                                'description' => 'Stage'
                            ]
                        ]
                    ],
                    'club' => [
                        'id' => '',
                        'uuid' => '',
                        'oe_key' => '53',
                        'short_name' => 'Murcia MURCIA-O',
                        'long_name' => 'Murcia MURCIA-O'
                    ],
                    'course' => [
                        'id' => '',
                        'uuid' => '',
                        'distance' => '2525.0',
                        'climb' => '45.0',
                        'controls' => (int)22,
                        'oe_key' => '9004',
                        'short_name' => '#16 aBbB'
                    ]
                ],
                (int)3 => [
                    'id' => '',
                    'uuid' => '',
                    'sicard' => '8059555',
                    'sex' => 'M',
                    'first_name' => 'Cesar',
                    'last_name' => 'Gurrea',
                    'db_id' => '36956',
                    'sicard_alt' => '',
                    'is_nc' => false,
                    'runner_results' => [
                        (int)0 => [
                            'id' => '',
                            'contributory' => false,
                            'stage_order' => (int)1,
                            'start_time' => '2025-09-13T12:54:53.000+02:00',
                            'finish_time' => '2025-09-13T13:24:03.000+02:00',
                            'time_seconds' => (int)1750,
                            'status_code' => '3',
                            'time_neutralization' => (int)0,
                            'time_adjusted' => (int)0,
                            'time_penalty' => (int)0,
                            'time_bonus' => (int)0,
                            'leg_number' => (int)4,
                            'result_type' => [
                                'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                                'description' => 'Stage'
                            ]
                        ]
                    ],
                    'club' => [
                        'id' => '',
                        'uuid' => '',
                        'oe_key' => '53',
                        'short_name' => 'Murcia MURCIA-O',
                        'long_name' => 'Murcia MURCIA-O'
                    ],
                    'course' => [
                        'id' => '',
                        'uuid' => '',
                        'distance' => '2775.0',
                        'climb' => '53.0',
                        'controls' => (int)24,
                        'oe_key' => '9004',
                        'short_name' => '#7 bAbB'
                    ]
                ]
            ]
        ];
        return [
            'configuration' => [
                'file' => '17relevoOS/tmp/CampeonatoAutonómicodeRelevosM_20250913_140711.xml',
                'extension' => 'XML',
                'utf' => false,
                'known_data' => true,
                'contents' => 'ResultList',
                'results_type' => 'Totals',
                'one_stage' => true,
                'source' => 'OS2010',
                'iof_version' => '3.0',
                'include_score' => false,
                'trailo_type' => 'Other',
                'trailo_at' => 'Other',
                'trailo_normal' => '0',
                'trailo_group' => '0',
                'totalization' => 'Other'
            ],
            'event' => [
                'id' => Event::FIRST_EVENT,
                'description' => 'relevo fedocv',
                'is_hidden' => false,
                'stages' => [
                    [
                        'id' => StagesFixture::STAGE_FEDO_2,
                        'description' => 'CampeonatoAutonómicodeRelevosM_FINAL-simple.xml 1',
                        'base_date' => '2025-09-14',
                        'base_time' => '10:30:00.000+02:00',
                        'order_number' => (int)1,
                        'classes' => [
                            [
                                'id' => '',
                                'uuid' => '',
                                'oe_key' => '1260',
                                'short_name' => 'SENIOR',
                                'long_name' => 'SENIOR',
                                'teams' => [
                                    $teamA,
                                    $teamB
                                ],
                                'course' => [
                                    'id' => '',
                                    'uuid' => '',
                                    'distance' => '2225.0',
                                    'climb' => '20.0',
                                    'controls' => (int)18,
                                    'oe_key' => '9004',
                                    'short_name' => '#10 aBaA'
                                ],
                                'runners' => []
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    public static function intermediateResults()
    {
        $classA = [
            'id' => '',
            'uuid' => '',
            'oe_key' => '1',
            'short_name' => 'E',
            'long_name' => 'Elite',
            'teams' => [],
            'course' => [
                'id' => '',
                'uuid' => '',
                'distance' => '5000.0',
                'climb' => '120.0',
                'controls' => (int) 4,
                'oe_key' => '1',
                'short_name' => 'R1'
            ],
            'classes_controls' => [
                (int) 0 => [
                    'control' => [
                        'station' => '32'
                    ]
                ],
                (int) 1 => [
                    'control' => [
                        'station' => '100'
                    ]
                ]
            ],
            'runners' => [
                (int) 0 => [
                    'id' => '',
                    'uuid' => '',
                    'sicard' => '8000001',
                    'sex' => 'M',
                    'first_name' => 'Javier',
                    'last_name' => 'Arufe Varela',
                    'bib_number' => '1',
                    'runner_results' => [
                        (int) 0 => [
                            'id' => '',
                            'start_time' => '2024-01-16T10:30:00.000+01:00',
                            'status_code' => StatusCode::OK,
                            'time_neutralization' => (int) 0,
                            'time_adjusted' => (int) 0,
                            'time_penalty' => (int) 0,
                            'time_bonus' => (int) 0,
                            'leg_number' => (int) 1,
                            'splits' => [
                                (int) 0 => [
                                    'sicard' => '8000001',
                                    'station' => '32',
                                    'points' => (int) 0,
                                    'reading_time' => '2024-01-16T10:56:47.000+01:00',
                                    'reading_milli' => (int) 1705399007000,
                                    'time_seconds' => (int) 1607,
                                    'bib_runner' => '1',
                                    'order_number' => (int) 1
                                ],
                                (int) 1 => [
                                    'sicard' => '8000001',
                                    'station' => '100',
                                    'points' => (int) 0,
                                    'bib_runner' => '1',
                                    'order_number' => (int) 2
                                ]
                            ],
                            'result_type' => [
                                'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                                'description' => 'Stage'
                            ]
                        ]
                    ],
                    'club' => [
                        'id' => '',
                        'uuid' => '',
                        'oe_key' => '1',
                        'short_name' => 'A Coruña LICEO',
                        'long_name' => 'A Coruña LICEO'
                    ]
                ],
                (int) 1 => [
                    'id' => '',
                    'uuid' => '',
                    'sicard' => '8000002',
                    'sex' => 'M',
                    'first_name' => 'Natalia',
                    'last_name' => 'Pedre Fernández',
                    'bib_number' => '2',
                    'runner_results' => [
                        (int) 0 => [
                            'id' => '',
                            'start_time' => '2024-01-16T10:40:00.000+01:00',
                            'status_code' => StatusCode::OK,
                            'time_neutralization' => (int) 0,
                            'time_adjusted' => (int) 0,
                            'time_penalty' => (int) 0,
                            'time_bonus' => (int) 0,
                            'leg_number' => (int) 1,
                            'splits' => [
                                (int) 0 => [
                                    'sicard' => '8000002',
                                    'station' => '32',
                                    'points' => (int) 0,
                                    'reading_time' => '2024-01-16T10:59:54.000+01:00',
                                    'reading_milli' => (int) 1705399194000,
                                    'time_seconds' => (int) 1194,
                                    'bib_runner' => '2',
                                    'order_number' => (int) 1
                                ],
                                (int) 1 => [
                                    'sicard' => '8000002',
                                    'station' => '100',
                                    'points' => (int) 0,
                                    'bib_runner' => '2',
                                    'order_number' => (int) 2
                                ]
                            ],
                            'result_type' => [
                                'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                                'description' => 'Stage'
                            ]
                        ]
                    ],
                    'club' => [
                        'id' => '',
                        'uuid' => '',
                        'oe_key' => '1',
                        'short_name' => 'A Coruña LICEO',
                        'long_name' => 'A Coruña LICEO'
                    ]
                ]
            ]
        ];
        return [
            'configuration' => [
                'file' => '/OReplayExamples/OE12_SimplestTest_03_Intermediate.xml',
                'extension' => 'XML',
                'utf' => true,
                'known_data' => true,
                'contents' => 'ResultList',
                'results_type' => 'Radiocontrols',
                'one_stage' => true,
                'source' => 'OEv12',
                'iof_version' => '3.0',
                'include_score' => false,
                'trailo_type' => 'Other',
                'trailo_at' => 'Other',
                'trailo_normal' => '0',
                'trailo_group' => '0'
            ],
            'event' => [
                'id' => Event::FIRST_EVENT,
                'description' => 'Test_Event',
                'is_hidden' => false,
                'stages' => [
                    [
                        'id' => StagesFixture::STAGE_FEDO_2,
                        'description' => 'iterim1',
                        'base_date' => '2024-01-16',
                        'base_time' => '10:30:00.000+01:00',
                        'controls' => [
                            (int) 0 => [
                                'station' => '32'
                            ],
                            (int) 1 => [
                                'station' => '100'
                            ]
                        ],
                        'classes' => [
                            $classA,
                        ]
                    ]
                ]
            ]
        ];
    }

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
                                                    'id' => '5542d38b-8bd3-40f4-913d-2c38048a0b04',
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
                                                    'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
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
                                                    'id' => '5542d38b-8bd3-40f4-913d-2c38048a0b04',
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
                                                    'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                                                    'description' => 'Stage'
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
                                                    'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                                                    'description' => 'Stage'
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
}
