<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller\UploadExamples;

use Results\Lib\Consts\StatusCode;
use Results\Model\Entity\Event;
use Results\Test\Fixture\StagesFixture;

class ResultExamples
{
    public static function resultSimpleFinishTime(): array
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
                                        'last_name' => 'Pino',
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
                                            'oe_key' => '85',
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

    public static function resultImport2CategoriesStarts()
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

    public static function resultImport2CategoriesSplits()
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
}
