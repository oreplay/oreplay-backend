<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

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
                                                'status_code' => '0',
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
                                                'status_code' => '0',
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
                                                'start_time' => '2014-07-06T13:09:01.523',
                                                'status_code' => '0',
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
                                        'sicard' => '820100',
                                        'first_name' => 'Francisco',
                                        'last_name' => 'Alvarez',
                                        'bib_number' => '255',
                                        'runner_results' => [
                                            [
                                                'id' => '',
                                                'stage_order' => (int) 1,
                                                'start_time' => '2014-07-06T13:11:00',
                                                'status_code' => '0',
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
                                                'status_code' => '0',
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
                                                'status_code' => '0',
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
}
