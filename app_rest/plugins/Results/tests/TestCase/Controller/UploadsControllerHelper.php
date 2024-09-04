<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use Results\Model\Entity\Event;
use Results\Test\Fixture\StagesFixture;

class UploadsControllerHelper
{
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
