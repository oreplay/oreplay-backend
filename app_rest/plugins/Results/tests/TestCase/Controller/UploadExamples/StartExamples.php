<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller\UploadExamples;

use Results\Lib\Consts\StatusCode;
use Results\Model\Entity\Event;
use Results\Model\Entity\ResultType;
use Results\Test\Fixture\StagesFixture;

class StartExamples
{
    public static function startImportSmall(): array
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
                                        'sex' => 'F',
                                        'runner_results' => [
                                            [
                                                'id' => '',
                                                'stage_order' => (int) 1,
                                                'start_time' => '2014-07-06T11:09:14.523+01:00',
                                                'status_code' => StatusCode::OK,
                                                'leg_number' => (int) 1,
                                                'result_type' => [
                                                    'id' => ResultType::STAGE,
                                                    'description' => 'Stage'
                                                ]
                                            ],
                                        ],
                                        'club' => [
                                            'id' => '',
                                            'uuid' => '',
                                            'oe_key' => '',
                                            'short_name' => '',
                                            'long_name' => ''
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
                                                    'id' => ResultType::STAGE,
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

    public static function entriesImportWithoutStartTimes(): array
    {
        return [
            'configuration' => [
                'source' => 'OE2010',
                'iof_version' => '3.0',
                'contents' => 'EntryList',
                'results_type' => 'Other',
                'trailo_type' => 'Other',
                'trailo_at' => 'Other',
                'trailo_normal' => '0',
                'trailo_group' => '0',
                'totalization' => 'Other',
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
                                        'sex' => 'F',
                                        'runner_results' => [
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

}
