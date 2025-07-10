<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller\UploadExamples;

use Results\Lib\Consts\StatusCode;
use Results\Lib\UploadConfigChecker;
use Results\Model\Entity\Event;
use Results\Model\Entity\ResultType;
use Results\Test\Fixture\StagesFixture;

class MixedExamples
{
    public static function importMixed(): array
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
                    'splits' => [],
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
                    'splits' => [],
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
                (int) 0 => [
                    'id' => '',
                    'position' => (int) 1,
                    'start_time' => '2001-01-01T10:35:00.000+01:00',
                    'time_seconds' => (int) 3600,
                    'status_code' => '0',
                    'time_behind' => (int) 0,
                    'splits' => [],
                    'result_type' => [
                        'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                        'description' => 'Stage'
                    ]
                ]
            ],
            'runners' => [
                $runnerA,
                $runnerB
            ]
        ];
        return [
            'configuration' => [
                'source_vendor' => 'sportSoftware',
                'source' => 'OE2010',
                'source_version' => '12.2',
                'contents' => 'ResultList',
                '_values_on_contents_' => 'StartList | ResultList',
                'results_type' => UploadConfigChecker::TYPE_MIXED,
                'utf' => true,
            ],
            'event' => [
                'id' => Event::FIRST_EVENT,
                'description' => 'Demo - 5 days of Italy 2014',
                'stages' => [
                    (int) 0 => [
                        'id' => StagesFixture::STAGE_FEDO_2,
                        'order_number' => 1,
                        'description' => 'Test stage Long distance',
                        'base_date' => '2025-07-09',
                        'base_time' => '10:30:00.000+01:00',
                        'controls' => [
                            [
                                'station' => '31'
                            ],
                            [
                                'station' => '100'
                            ]
                        ],
                        'classes' => [
                            [
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
                                    [
                                        'id' => '',
                                        'uuid' => '',
                                        'sicard' => '8011750',
                                        'sicard_alt' => '',
                                        'license' => '',
                                        'birth_date' => '',
                                        'first_name' => 'Francisco',
                                        'last_name' => 'One Runner Downloaded',
                                        'bib_number' => '255',
                                        'sex' => 'M',
                                        'country' => 'Spain',
                                        'region' => 'Madrid',
                                        'is_nc' => false,
                                        'runner_results' => [
                                            [
                                                'id' => '',
                                                'position' => 1,
                                                'start_time' => '2024-09-29T11:00:00.000',
                                                'finish_time' => '2024-09-29T12:26:54.000',
                                                'time_seconds' => 5214,
                                                'status_code' => StatusCode::OK,
                                                'time_behind' => 0,
                                                'time_neutralization' => 0,
                                                'time_adjusted' => 0,
                                                'time_penalty' => 0,
                                                'time_bonus' => 0,
                                                'points_final' => 0,
                                                'points_adjusted' => 0,
                                                'points_penalty' => 0,
                                                'points_bonus' => 0,
                                                'leg_number' => 1,
                                                'splits' => [
                                                    [
                                                        'sicard' => '8011750',
                                                        'station' => '31',
                                                        'points' => 0,
                                                        'reading_time' => '2024-01-28T10:15:05.000',
                                                        'reading_milli' => 1706433305000,
                                                        'time_seconds' => 605,
                                                        'order_number' => 1,
                                                        'is_intermediate' => false,
                                                    ],
                                                    [
                                                        'sicard' => '8011750',
                                                        'station' => '100',
                                                        'points' => 0,
                                                        'reading_time' => '2024-01-28T10:18:37.000',
                                                        'reading_milli' => 1706433517000,
                                                        'time_seconds' => 817,
                                                        'order_number' => 2,
                                                        'is_intermediate' => false,
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
                                            'oe_key' => '24738',
                                            'short_name' => 'BRIGHTNET',
                                            'long_name' => 'BRIGHTNET'
                                        ]
                                    ],
                                    [
                                        'id' => '',
                                        'uuid' => '',
                                        'sicard' => '889818',
                                        'sicard_alt' => '889818',
                                        'license' => '',
                                        'birth_date' => '',
                                        'first_name' => 'Carlos',
                                        'last_name' => 'One Runner Not Started Yet',
                                        'bib_number' => '359',
                                        'sex' => 'M',
                                        'country' => 'Spain',
                                        'region' => 'Madrid',
                                        'is_nc' => false,
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
                                            'oe_key' => '24738',
                                            'short_name' => 'BRIGHTNET',
                                            'long_name' => 'BRIGHTNET'
                                        ]
                                    ],
                                    [
                                        'id' => '',
                                        'uuid' => '',
                                        'sicard' => '8000001',
                                        'sicard_alt' => '',
                                        'license' => '',
                                        'birth_date' => '',
                                        'sex' => 'M',
                                        'first_name' => 'Javier',
                                        'last_name' => 'One Runner With Intermediates',
                                        'bib_number' => '1',
                                        'country' => 'Spain',
                                        'region' => 'Madrid',
                                        'is_nc' => false,
                                        'runner_results' => [
                                            [
                                                'id' => '',
                                                'start_time' => '2024-01-16T10:30:00.000+01:00',
                                                'status_code' => StatusCode::OK,
                                                'time_neutralization' => 0,
                                                'time_adjusted' => 0,
                                                'time_penalty' => 0,
                                                'time_bonus' => 0,
                                                'leg_number' => 1,
                                                'splits' => [
                                                    (int) 0 => [
                                                        'sicard' => '8000001',
                                                        'station' => '31',
                                                        'points' => (int) 0,
                                                        'reading_time' => '2024-01-16T10:56:47.000+01:00',
                                                        'reading_milli' => (int) 1705399007000,
                                                        'time_seconds' => (int) 1607,
                                                        'bib_runner' => '1',
                                                        'order_number' => (int) 1,
                                                        'is_intermediate' => true,
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
                                            'oe_key' => '1',
                                            'short_name' => 'A Coruña LICEO',
                                            'long_name' => 'A Coruña LICEO'
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'id' => '',
                                'uuid' => '',
                                'oe_key' => '20',
                                'short_name' => 'Relay',
                                'long_name' => 'Relay with 1 team or many, with or without splits',
                                'course' => [
                                    'id' => '',
                                    'uuid' => '',
                                    'distance' => '4710.0',
                                    'climb' => '230.0',
                                    'controls' => (int) 19,
                                    'oe_key' => '30',
                                    'short_name' => 'WE/M20'
                                ],
                                'teams' => [$team]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
