<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use Results\Lib\Consts\StatusCode;
use Results\Model\Entity\Event;
use Results\Test\Fixture\StagesFixture;

class UploadsControllerExamples
{
    public static function exampleSimpleFinishTime(): array
    {
        return [
            'configuration' => [
                'file' => '/path/tmp/Test-FinalResults-jWRMC1Ia-106.xml',
                'extension' => 'XML',
                'utf' => true,
                'known_data' => true,
                'contents' => 'ResultList',
                'results_type' => 'Totals',
                'one_stage' => true,
                'source' => 'OEv12',
                'iof_version' => '3.0'
            ],
            'event' => [
                'id' => Event::FIRST_EVENT,
                'description' => 'la prueba de adri',
                'is_hidden' => false,
                'stages' => [
                    [
                        'id' => StagesFixture::STAGE_FEDO_2,
                        'description' => 'adri 1 stage',
                        'classes' => [
                            [
                                'id' => '',
                                'uuid' => '',
                                'oe_key' => '1',
                                'short_name' => '10 Mas30F',
                                'long_name' => '10 km Master 30 F',
                                'course' => [
                                    'id' => '',
                                    'uuid' => '',
                                    'distance' => '10000.0',
                                    'climb' => '',
                                    'controls' => (int) 4,
                                    //'oe_key' => '2',
                                    //'short_name' => '10 km F',
                                    'long_name' => '10 km F',
                                ],
                                'runners' => [
                                    [
                                        'id' => '',
                                        'uuid' => '',
                                        'sicard' => '4440522',
                                        'sex' => 'F',
                                        'first_name' => 'Maria',
                                        'last_name' => 'Ballesteros',
                                        'bib_number' => '125',
                                        'runner_results' => [
                                            [
                                                'id' => '',
                                                'position' => (int) 1,
                                                'start_time' => '2024-09-29T11:00:00.000',
                                                'finish_time' => '2024-09-29T12:26:54.000',
                                                'time_seconds' => (int) 5214,
                                                'status_code' => StatusCode::OK,
                                                'time_behind' => (int) 0,
                                                'time_neutralization' => (int) 0,
                                                'time_adjusted' => (int) 0,
                                                'time_penalty' => (int) 0,
                                                'time_bonus' => (int) 0,
                                                'points_final' => (int) 0,
                                                'points_adjusted' => (int) 0,
                                                'points_penalty' => (int) 0,
                                                'points_bonus' => (int) 0,
                                                'leg_number' => (int) 1,
                                                'splits' => [
                                                    [
                                                        'sicard' => '8011750',
                                                        'station' => '31',
                                                        'points' => 0,
                                                        'reading_time' => '2024-01-28T10:15:05.000',
                                                        'reading_milli' => 1706433305000,
                                                        'time_seconds' => 605,
                                                        'order_number' => 1
                                                    ],
                                                    [
                                                        'sicard' => '8011750',
                                                        'station' => '33',
                                                        'points' => 0,
                                                        'reading_time' => '2024-01-28T10:18:37.000',
                                                        'reading_milli' => 1706433517000,
                                                        'time_seconds' => 817,
                                                        'order_number' => 2
                                                    ],
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
                                            //'oe_key' => '12',
                                            //'short_name' => 'Independiente',
                                            'long_name' => 'Independiente'
                                        ]
                                    ],
                                    [
                                        'id' => '',
                                        'uuid' => '',
                                        'sicard' => '4540555',
                                        'sex' => 'M',
                                        'first_name' => 'Antonio',
                                        'last_name' => 'Velazquez',
                                        'bib_number' => '105',
                                        'runner_results' => [
                                            (int) 0 => [
                                                'id' => '',
                                                'position' => (int) 2,
                                                'start_time' => '2024-09-29T11:00:00.000',
                                                'finish_time' => '2024-09-29T11:48:49.000',
                                                'time_seconds' => (int) 2929,
                                                'status_code' => StatusCode::OK,
                                                'time_behind' => (int) 44,
                                                'time_neutralization' => (int) 0,
                                                'time_adjusted' => (int) 0,
                                                'time_penalty' => (int) 0,
                                                'time_bonus' => (int) 0,
                                                'points_final' => (int) 0,
                                                'points_adjusted' => (int) 0,
                                                'points_penalty' => (int) 0,
                                                'points_bonus' => (int) 0,
                                                'leg_number' => (int) 1,
                                                'splits' => [
                                                    [
                                                        'sicard' => '4540555',
                                                        'station' => '33',
                                                        'points' => 0,
                                                        'reading_time' => '2024-01-28T10:32:35.000',
                                                        'reading_milli' => 1706433617000,
                                                        'time_seconds' => 822,
                                                        'order_number' => 1
                                                    ],
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
                                            'oe_key' => '12',
                                            'short_name' => 'Independiente',
                                            'long_name' => 'Independiente'
                                        ]
                                    ],
                                ]
                            ],
                        ]
                    ]
                ]
            ]
        ];
    }

    public static function exampleImportSmall(): array
    {
        return [
            'configuration' => [
                'source' => 'OE2010',
                'iof_version' => '3.0',
                'contents' => 'StartList',
                'results_type' => 'Other',
            ],
            'event' => [
                'id' => Event::FIRST_EVENT,
                'description' => 'Demo - 5 days of Italy 2014',
                'stages' => [
                    (int) 0 => [
                        'id' => StagesFixture::STAGE_FEDO_2,
                        'order_number' => (int) 1,
                        'classes' => [
                            (int) 0 => [
                                'id' => '',
                                'uuid' => '',
                                'oe_key' => '10',
                                'short_name' => 'ME',
                                'long_name' => 'M Elite',
                                'course' => [
                                    'id' => '',
                                    'uuid' => '',
                                    'distance' => '5660.0',
                                    'climb' => '280.0',
                                    'controls' => (int) 22,
                                    'oe_key' => '26',
                                    'short_name' => 'ME'
                                ],
                                'runners' => [
                                    (int) 0 => [
                                        'id' => '',
                                        'uuid' => '',
                                        'sicard' => '889818',
                                        'first_name' => 'Carlos',
                                        'last_name' => 'Alonso',
                                        'bib_number' => '359',
                                        'runner_results' => [
                                            [
                                                'id' => '',
                                                'stage_order' => (int) 1,
                                                'start_time' => '2014-07-06T11:09:14.523+01:00',
                                                'status_code' => StatusCode::OK,
                                                'leg_number' => (int) 1,
                                                'result_type' => [
                                                    'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                                                    'description' => 'Stage'
                                                ]
                                            ],
                                        ],
                                        'club' => [
                                            'id' => '',
                                            'uuid' => '',
                                            'oe_key' => '24738',
                                            'short_name' => 'BRIGHTNET',
                                            'long_name' => 'BRIGHTNET'
                                        ]
                                    ],
                                    (int) 1 => [
                                        'id' => '',
                                        'uuid' => '',
                                        'sicard' => '',
                                        'first_name' => 'Francisco',
                                        'last_name' => 'Alvarez',
                                        'bib_number' => '255',
                                        'runner_results' => [
                                            [
                                                'id' => '',
                                                'stage_order' => (int) 1,
                                                'start_time' => '2014-07-06T13:11:00',
                                                'status_code' => StatusCode::OK,
                                                'leg_number' => (int) 1,
                                                'result_type' => [
                                                    'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                                                    'description' => 'Stage'
                                                ]
                                            ],
                                        ],
                                        'club' => [
                                            'id' => '',
                                            'uuid' => '',
                                            'oe_key' => '24738',
                                            'short_name' => 'BRIGHTNET',
                                            'long_name' => 'BRIGHTNET'
                                        ]
                                    ]
                                ]
                            ],
                            (int) 1 => [
                                'id' => '',
                                'uuid' => '',
                                'oe_key' => '20',
                                'short_name' => 'WE',
                                'long_name' => 'W Elite',
                                'course' => [
                                    'id' => '',
                                    'uuid' => '',
                                    'distance' => '4710.0',
                                    'climb' => '230.0',
                                    'controls' => (int) 19,
                                    'oe_key' => '30',
                                    'short_name' => 'WE/M20'
                                ],
                                'runners' => [
                                    (int) 0 => [
                                        'id' => '',
                                        'uuid' => '',
                                        'sicard' => '7504274',
                                        'first_name' => 'Ana',
                                        'last_name' => 'Gomez',
                                        'bib_number' => '1348',
                                        'runner_results' => [
                                            [
                                                'id' => '',
                                                'stage_order' => (int) 1,
                                                'start_time' => '2014-07-06T13:22:00',
                                                'status_code' => StatusCode::OK,
                                                'leg_number' => (int) 1,
                                                'result_type' => [
                                                    'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                                                    'description' => 'Stage'
                                                ]
                                            ],
                                        ],
                                        'club' => [
                                            'id' => '',
                                            'uuid' => '',
                                            'oe_key' => '',
                                            'short_name' => 'Tullinge SK',
                                            'long_name' => 'Tullinge SK'
                                        ]
                                    ],
                                    (int) 1 => [
                                        'id' => '',
                                        'uuid' => '',
                                        'sicard' => '889312',
                                        'first_name' => 'Maria',
                                        'last_name' => 'Rodriguez',
                                        'bib_number' => '1512',
                                        'runner_results' => [
                                            [
                                                'id' => '',
                                                'stage_order' => (int) 1,
                                                'start_time' => '2014-07-06T13:26:00',
                                                'status_code' => StatusCode::OK,
                                                'leg_number' => (int) 1,
                                                'result_type' => [
                                                    'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                                                    'description' => 'Stage'
                                                ]
                                            ],
                                        ],
                                        'club' => [
                                            'id' => '',
                                            'uuid' => '',
                                            'oe_key' => '',
                                            'short_name' => 'Tullinge SK',
                                            'long_name' => 'Tullinge SK'
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

    public static function exampleImport2CategoriesStarts()
    {
        return [
            'configuration' => [
                'file' => '/OReplayExamples/StartsCEO-Media-FE.xml',
                'extension' => 'XML',
                'utf' => true,
                'known_data' => true,
                'contents' => 'ResultList',
                'results_type' => 'Breakdown',
                'one_stage' => true,
                'source' => 'OEv12',
                'iof_version' => '3.0',
                'include_score' => false
            ],
            'event' => [
                'id' => Event::FIRST_EVENT,
                'description' => 'Test_Event',
                'is_hidden' => false,
                'stages' => [
                    (int) 0 => [
                        'id' => StagesFixture::STAGE_FEDO_2,
                        'description' => 'Test_Stage',
                        'base_date' => '2024-02-01',
                        'base_time' => '11:00:00.000',
                        'order_number' => (int) 1,
                        'classes' => [
                            (int) 0 => [
                                'id' => '',
                                'uuid' => '',
                                'oe_key' => '1',
                                'short_name' => 'U-10',
                                'long_name' => 'U-10',
                                'teams' => [],
                                'course' => [
                                    'id' => '',
                                    'uuid' => '',
                                    'distance' => '1850.0',
                                    'climb' => '30.0',
                                    'controls' => (int) 9,
                                    'oe_key' => '9007',
                                    'short_name' => 'R07'
                                ],
                                'runners' => [
                                    (int) 0 => [
                                        'id' => '',
                                        'uuid' => '',
                                        'sicard' => '8502455',
                                        'sex' => 'F',
                                        'first_name' => 'Maria',
                                        'last_name' => 'Alvarez',
                                        'bib_number' => '3874',
                                        'runner_results' => [
                                            [
                                                'id' => '',
                                                'stage_order' => (int) 1,
                                                'start_time' => '2024-10-18T09:56:00.000',
                                                'status_code' => StatusCode::OK,
                                                'leg_number' => (int) 1,
                                                'result_type' => [
                                                    'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                                                    'description' => 'Stage'
                                                ]
                                            ],
                                        ],
                                        'club' => [
                                            'id' => '',
                                            'uuid' => '',
                                            'oe_key' => '200',
                                            'short_name' => 'Valencia VERD3',
                                            'long_name' => 'Valencia VERD3'
                                        ]
                                    ]
                                ]
                            ],
                            (int) 1 => [
                                'id' => '',
                                'uuid' => '',
                                'oe_key' => '42',
                                'short_name' => 'O ROJO F',
                                'long_name' => 'OPEN ROJO F',
                                'teams' => [],
                                'course' => [
                                    'id' => '',
                                    'uuid' => '',
                                    'distance' => '2675.0',
                                    'climb' => '85.0',
                                    'controls' => (int) 14,
                                    'oe_key' => '9003',
                                    'short_name' => 'R03'
                                ],
                                'runners' => [
                                    (int) 0 => [
                                        'id' => '',
                                        'uuid' => '',
                                        'sicard' => '2063133',
                                        'sex' => 'F',
                                        'first_name' => 'Ana',
                                        'last_name' => 'Rodriguez',
                                        'bib_number' => '1329',
                                        'runner_results' => [
                                            [
                                                'id' => '',
                                                'stage_order' => (int) 1,
                                                'start_time' => '2024-10-18T09:56:00.000',
                                                'status_code' => StatusCode::OK,
                                                'leg_number' => (int) 1,
                                                'result_type' => [
                                                    'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                                                    'description' => 'Stage'
                                                ]
                                            ],
                                        ],
                                        'club' => [
                                            'id' => '',
                                            'uuid' => '',
                                            'oe_key' => '51',
                                            'short_name' => 'Sevilla MONTELLANO',
                                            'long_name' => 'Sevilla MONTELLANO'
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

    public static function exampleImport2CategoriesSplits()
    {
        return [
            'configuration' => [
                'file' => '/OReplayExamples/ParcialesCEO-Media-FE.xml',
                'extension' => 'XML',
                'utf' => true,
                'known_data' => true,
                'contents' => 'ResultList',
                'results_type' => 'Breakdown',
                'one_stage' => true,
                'source' => 'OEv12',
                'iof_version' => '3.0',
                'include_score' => false
            ],
            'event' => [
                'id' => Event::FIRST_EVENT,
                'description' => 'Test_Event',
                'is_hidden' => false,
                'stages' => [
                    (int) 0 => [
                        'id' => StagesFixture::STAGE_FEDO_2,
                        'description' => 'Test_Stage',
                        'base_date' => '2024-02-01',
                        'base_time' => '11:00:00.000',
                        'order_number' => (int) 1,
                        'classes' => [
                            (int) 0 => [
                                'id' => '',
                                'uuid' => '',
                                'oe_key' => '1',
                                'short_name' => 'U-10',
                                'long_name' => 'U-10',
                                'teams' => [],
                                'course' => [
                                    'id' => '',
                                    'uuid' => '',
                                    'distance' => '1850.0',
                                    'climb' => '30.0',
                                    'controls' => (int) 9,
                                    'oe_key' => '9007',
                                    'short_name' => 'R07'
                                ],
                                'runners' => [
                                    (int) 0 => [
                                        'id' => '',
                                        'uuid' => '',
                                        'sicard' => '8502455',
                                        'sex' => 'F',
                                        'first_name' => 'Maria',
                                        'last_name' => 'Alvarez',
                                        'bib_number' => '3874',
                                        'runner_results' => [
                                            (int) 0 => [
                                                'id' => '',
                                                'position' => (int) 1,
                                                'stage_order' => (int) 1,
                                                'start_time' => '2024-10-18T09:56:00.000',
                                                'finish_time' => '2024-10-18T10:09:40.000',
                                                'time_seconds' => (int) 820,
                                                'status_code' => StatusCode::OK,
                                                'time_behind' => (int) 0,
                                                'time_neutralization' => (int) 0,
                                                'time_adjusted' => (int) 0,
                                                'time_penalty' => (int) 0,
                                                'time_bonus' => (int) 0,
                                                'leg_number' => (int) 1,
                                                'splits' => [
                                                    (int) 0 => [
                                                        'sicard' => '8502455',
                                                        'station' => '89',
                                                        'points' => (int) 0,
                                                        'stage_order' => (int) 1,
                                                        'reading_time' => '2024-10-18T10:09:26.000',
                                                        'reading_milli' => (int) 1729238966000,
                                                        'time_seconds' => (int) 806,
                                                        'bib_runner' => '3874',
                                                        'order_number' => (int) 9
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
                                            'oe_key' => '200',
                                            'short_name' => 'Valencia VERD3',
                                            'long_name' => 'Valencia VERD3'
                                        ]
                                    ]
                                ]
                            ],
                            (int) 1 => [
                                'id' => '',
                                'uuid' => '',
                                'oe_key' => '42',
                                'short_name' => 'O ROJO F',
                                'long_name' => 'OPEN ROJO F',
                                'teams' => [],
                                'course' => [
                                    'id' => '',
                                    'uuid' => '',
                                    'distance' => '2675.0',
                                    'climb' => '85.0',
                                    'controls' => (int) 14,
                                    'oe_key' => '9003',
                                    'short_name' => 'R03'
                                ],
                                'runners' => [
                                    (int) 0 => [
                                        'id' => '',
                                        'uuid' => '',
                                        'sicard' => '2063133',
                                        'sex' => 'F',
                                        'first_name' => 'Ana',
                                        'last_name' => 'Rodriguez',
                                        'bib_number' => '1329',
                                        'runner_results' => [
                                            (int) 0 => [
                                                'id' => '',
                                                'stage_order' => (int) 1,
                                                'status_code' => StatusCode::DNS,
                                                'time_neutralization' => (int) 0,
                                                'time_adjusted' => (int) 0,
                                                'time_penalty' => (int) 0,
                                                'time_bonus' => (int) 0,
                                                'leg_number' => (int) 1,
                                                'splits' => [
                                                    (int) 0 => [
                                                        'sicard' => '2063133',
                                                        'station' => '89',
                                                        'points' => (int) 0,
                                                        'stage_order' => (int) 1,
                                                        'bib_runner' => '1329',
                                                        'order_number' => (int) 14
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
                                            'oe_key' => '51',
                                            'short_name' => 'Sevilla MONTELLANO',
                                            'long_name' => 'Sevilla MONTELLANO'
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

    public static function startTimesWithOneRunnerAndOneTeam()
    {
        $classRunner = [
            'id' => '',
            'uuid' => '',
            'oe_key' => '57',
            'long_name' => 'Individual',
            'teams' => [],
            'course' => [
                'id' => '',
                'uuid' => '',
                'distance' => '',
                'climb' => '',
                'controls' => (int) 0,
                'oe_key' => '25',
                'short_name' => 'Full Score'
            ],
            'runners' => [
                [
                    'id' => '',
                    'uuid' => '',
                    'sicard' => '8530222',
                    'sex' => 'M',
                    'first_name' => 'Jorge',
                    'last_name' => 'Alonsolo',
                    'runner_results' => [
                        (int) 0 => [
                            'id' => '',
                            'stage_order' => (int) 1,
                            'start_time' => '2024-11-10T10:30:00.000+01:00',
                            'status_code' => StatusCode::OK,
                            'leg_number' => (int) 1
                        ]
                    ],
                    'club' => [
                        'id' => '',
                        'uuid' => '',
                        'oe_key' => '2690',
                        'long_name' => 'Albacete BMT CASAS DE VES'
                    ]
                ]
            ]
        ];
        $classTeam = [
            'id' => '',
            'uuid' => '',
            'oe_key' => '76',
            'long_name' => 'DUAL.TEAM',
            'teams' => [
                [
                    'id' => '',
                    'uuid' => '',
                    'bib_number' => '',
                    'team_name' => 'Couupless',
                    'club' => [
                        'id' => '',
                        'uuid' => '',
                        'oe_key' => '65',
                        'long_name' => 'Alicante SANT_JOAN'
                    ],
                    'team_results' => [
                        (int) 0 => [
                            'id' => '',
                            'stage_order' => (int) 1,
                            'start_time' => '2024-11-10T10:30:00.000+01:00',
                            'status_code' => StatusCode::OK
                        ]
                    ],
                    'runners' => [
                        [
                            'id' => '',
                            'uuid' => '',
                            'sicard' => '8008999',
                            'sex' => 'M',
                            'first_name' => 'Paco',
                            'last_name' => 'Morenoa',
                            'runner_results' => [
                                (int) 0 => [
                                    'id' => '',
                                    'stage_order' => (int) 1,
                                    'start_time' => '2024-11-10T10:30:00.000+01:00',
                                    'status_code' => StatusCode::OK,
                                    'leg_number' => (int) 1
                                ]
                            ],
                            'club' => [
                                'id' => '',
                                'uuid' => '',
                                'oe_key' => '65',
                                'long_name' => 'Alicante SANT_JOAN'
                            ],
                            'course' => [
                                'id' => '',
                                'uuid' => '',
                                'distance' => '',
                                'climb' => '',
                                'controls' => (int) 0,
                                'oe_key' => '25',
                                'short_name' => 'Full Score'
                            ]
                        ],
                        [
                            'id' => '',
                            'uuid' => '',
                            'sicard' => '1398555',
                            'sex' => 'M',
                            'first_name' => 'Andrea',
                            'last_name' => 'Ponceb',
                            'runner_results' => [
                                (int) 0 => [
                                    'id' => '',
                                    'stage_order' => (int) 1,
                                    'start_time' => '2024-11-10T10:30:00.000+01:00',
                                    'status_code' => StatusCode::OK,
                                    'leg_number' => (int) 1
                                ]
                            ],
                            'club' => [
                                'id' => '',
                                'uuid' => '',
                                'oe_key' => '65',
                                'long_name' => 'Alicante SANT_JOAN'
                            ],
                            'course' => [
                                'id' => '',
                                'uuid' => '',
                                'distance' => '',
                                'climb' => '',
                                'controls' => (int) 0,
                                'oe_key' => '25',
                                'short_name' => 'Full Score'
                            ]
                        ]
                    ]
                ],
            ],
            'course' => [
                'id' => '',
                'uuid' => '',
                'distance' => '',
                'climb' => '',
                'controls' => (int) 0,
                'oe_key' => '25',
                'short_name' => 'Full Score'
            ],
            'runners' => []
        ];
        return [
            'configuration' => [
                'file' => '/OReplayExamples/start-times-rogaining-meos.xml',
                'extension' => 'XML',
                'utf' => false,
                'known_data' => true,
                'contents' => 'StartList',
                'results_type' => 'Other',
                'one_stage' => true,
                'source' => 'MeOS',
                'iof_version' => '3.0',
                'include_score' => false
            ],
            'event' => [
                'id' => Event::FIRST_EVENT,
                'description' => 'Test_Event',
                'is_hidden' => false,
                'stages' => [
                    [
                        'id' => StagesFixture::STAGE_FEDO_2,
                        'description' => 'Test_Stage',
                        'base_date' => '2024-02-01',
                        'base_time' => '11:00:00.000+01:00',
                        'order_number' => (int) 1,
                        'classes' => [
                            $classRunner,
                            $classTeam,
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
}
