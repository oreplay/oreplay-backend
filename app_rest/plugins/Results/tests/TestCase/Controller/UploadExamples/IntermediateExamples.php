<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller\UploadExamples;

use Results\Lib\Consts\StatusCode;
use Results\Model\Entity\Event;
use Results\Test\Fixture\StagesFixture;

class IntermediateExamples
{
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

    public static function itermediateWithDuplicatedBibs()
    {
        return [
            'configuration' => [
                'file' => 'D:zz_resulRadio.xml',
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
                'trailo_group' => '0',
                'totalization' => 'Other'
            ],
            'event' => [
                'id' => Event::FIRST_EVENT,
                'description' => 'Championship',
                'is_hidden' => false,
                'stages' => [
                    (int) 0 => [
                        'id' => StagesFixture::STAGE_FEDO_2,
                        'description' => 'Long, Forest',
                        'controls' => [
                            (int) 0 => [
                                'station' => '165'
                            ],
                            (int) 1 => [
                                'station' => '158'
                            ],
                            (int) 2 => [
                                'station' => '136'
                            ],
                            (int) 3 => [
                                'station' => '137'
                            ]
                        ],
                        'classes' => [
                            (int) 0 => [
                                'id' => '',
                                'uuid' => '',
                                'oe_key' => '1',
                                'short_name' => 'INF FEM',
                                'long_name' => 'INFANTIL FEMENINO',
                                'teams' => [],
                                'course' => [
                                    'id' => '',
                                    'uuid' => '',
                                    'distance' => '3850.0',
                                    'climb' => '160.0',
                                    'controls' => (int) 14,
                                    'oe_key' => '9002',
                                    'short_name' => 'F - INFANTIL'
                                ],
                                'classes_controls' => [
                                    (int) 0 => [
                                        'control' => [
                                            'station' => '165'
                                        ]
                                    ],
                                    (int) 1 => [
                                        'control' => [
                                            'station' => '158'
                                        ]
                                    ]
                                ],
                                'runners' => [
                                    (int) 0 => [
                                        'id' => '',
                                        'uuid' => '',
                                        'sicard' => '8502422',
                                        'sex' => 'F',
                                        'first_name' => 'Sara',
                                        'last_name' => 'Alonso',
                                        'db_id' => '19808',
                                        'bib_number' => '1',
                                        'sicard_alt' => '',
                                        'is_nc' => false,
                                        'runner_results' => [
                                            (int) 0 => [
                                                'id' => '',
                                                'start_time' => '2025-06-20T09:40:00.000+02:00',
                                                'status_code' => '0',
                                                'time_neutralization' => (int) 0,
                                                'time_adjusted' => (int) 0,
                                                'time_penalty' => (int) 0,
                                                'time_bonus' => (int) 0,
                                                'leg_number' => (int) 1,
                                                'splits' => [
                                                    (int) 0 => [
                                                        'sicard' => '8502422',
                                                        'station' => '165',
                                                        'points' => (int) 0,
                                                        'bib_runner' => '1',
                                                        'order_number' => (int) 1
                                                    ],
                                                    (int) 1 => [
                                                        'sicard' => '8502422',
                                                        'station' => '158',
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
                                            'oe_key' => '10',
                                            'short_name' => 'ARAGÓN',
                                            'long_name' => 'ARAGÓN'
                                        ]
                                    ],
                                    (int) 1 => [
                                        'id' => '',
                                        'uuid' => '',
                                        'sicard' => '8525829',
                                        'sex' => 'F',
                                        'first_name' => 'Sonia',
                                        'last_name' => 'Estibaliz',
                                        'db_id' => '14844',
                                        'bib_number' => '1',
                                        'sicard_alt' => '',
                                        'is_nc' => false,
                                        'runner_results' => [
                                            (int) 0 => [
                                                'id' => '',
                                                'start_time' => '2025-06-20T09:43:00.000+02:00',
                                                'status_code' => '0',
                                                'time_neutralization' => (int) 0,
                                                'time_adjusted' => (int) 0,
                                                'time_penalty' => (int) 0,
                                                'time_bonus' => (int) 0,
                                                'leg_number' => (int) 1,
                                                'splits' => [
                                                    (int) 0 => [
                                                        'sicard' => '8525829',
                                                        'station' => '165',
                                                        'points' => (int) 0,
                                                        'reading_time' => '2025-06-20T10:13:48.000+02:00',
                                                        'reading_milli' => (int) 1750407228000,
                                                        'time_seconds' => (int) 1848,
                                                        'bib_runner' => '1',
                                                        'order_number' => (int) 1
                                                    ],
                                                    (int) 1 => [
                                                        'sicard' => '8525829',
                                                        'station' => '158',
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
                                            'oe_key' => '17',
                                            'short_name' => 'CASTILLA Y LEÓN',
                                            'long_name' => 'CASTILLA Y LEÓN'
                                        ]
                                    ],
                                    (int) 2 => [
                                        'id' => '',
                                        'uuid' => '',
                                        'sicard' => '8655840',
                                        'sex' => 'F',
                                        'first_name' => 'Elena',
                                        'last_name' => 'Rodriguez',
                                        'db_id' => '31758',
                                        'bib_number' => '1',
                                        'sicard_alt' => '',
                                        'is_nc' => false,
                                        'runner_results' => [
                                            (int) 0 => [
                                                'id' => '',
                                                'start_time' => '2025-06-20T09:37:00.000+02:00',
                                                'status_code' => '0',
                                                'time_neutralization' => (int) 0,
                                                'time_adjusted' => (int) 0,
                                                'time_penalty' => (int) 0,
                                                'time_bonus' => (int) 0,
                                                'leg_number' => (int) 1,
                                                'splits' => [
                                                    (int) 0 => [
                                                        'sicard' => '8655840',
                                                        'station' => '165',
                                                        'points' => (int) 0,
                                                        'bib_runner' => '1',
                                                        'order_number' => (int) 1
                                                    ],
                                                    (int) 1 => [
                                                        'sicard' => '8655840',
                                                        'station' => '158',
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
                                            'oe_key' => '7',
                                            'short_name' => 'LA RIOJA',
                                            'long_name' => 'LA RIOJA'
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
