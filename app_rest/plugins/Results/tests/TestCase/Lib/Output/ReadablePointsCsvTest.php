<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Output;

use Cake\TestSuite\TestCase;
use Results\Lib\Output\ReadablePointsCsv;

class ReadablePointsCsvTest extends TestCase
{
    protected $fixtures = [
    ];

    public function testRender()
    {
        $renderer = new ReadablePointsCsv();
        $csv = $renderer->setResults(['F-14' => $this->_getResults()])->render();
        $expected = <<<CSV
            Pos;Name;Club;Pts;Stage 1;;2025 2 LGOP;;2025 3 LGOP;;2025 4 LGOP;;
            F-14
            1;Sabela Diaz;Pontevedra AROMON;383;-;;100;;100;;100;;
            2;Noelia Orozco;GALLAECIA_RAID;318;-;;67;;75;;76;;100;;
            3;Elena Ponce;ARTABROS;275;0;(not contributory);79;;61;;51;;84;;


            CSV;
        $this->assertEquals($expected, $csv);
    }

    private function _getResults(): array
    {
        $results = [
            [
                'id' => '289ef397-8b42-4726-90c3-fc3c9fc3ef1f',
                'bib_number' => '4255',
                'is_nc' => false,
                'eligibility' => null,
                'sicard' => '8500000',
                'sex' => 'F',
                'leg_number' => (int)1,
                'created' => '2025-09-24T18:18:22.751+00:00',
                'class' => [
                    'id' => '5542d502-20e1-4ed1-96db-d4287845b2b0',
                    'short_name' => 'F-14',
                    'long_name' => 'F-14'
                ],
                'club' => [
                    'id' => '596a3fb8-d44e-4b7c-a26f-af4e32127e93',
                    'short_name' => 'Pontevedra AROMON'
                ],
                'full_name' => 'Sabela Diaz',
                'stage' => null,
                'overalls' => [
                    'parts' => [
                        /*
                        (int)0 => [
                            'id' => 'cc783b47-67a0-4ecb-9856-5815e107903b',
                            'stage_order' => (int)1,
                            'upload_type' => 'total_points',
                            'stage' => [
                                'id' => '00e6408f-482e-44a2-b8ec-23c34af16d3b',
                                'description' => '2025 1 LGOP'
                            ],
                            'position' => (int)4,
                            'status_code' => '0',
                            'is_nc' => false,
                            'contributory' => true,
                            'time_seconds' => (int)0,
                            'time_behind' => (int)0,
                            'points_final' => (int)83,
                            'points_behind' => null,
                            'note' => null
                        ],
                        */
                        (int)1 => [
                            'id' => '410ec2b3-9d74-4c1a-abee-e76742648836',
                            'stage_order' => (int)2,
                            'upload_type' => 'total_points',
                            'stage' => [
                                'id' => 'bdc6c03e-489b-498c-bffe-beb04311411e',
                                'description' => '2025 2 LGOP'
                            ],
                            'position' => (int)1,
                            'status_code' => '0',
                            'is_nc' => false,
                            'contributory' => true,
                            'time_seconds' => (int)0,
                            'time_behind' => (int)0,
                            'points_final' => (int)100,
                            'points_behind' => null,
                            'note' => null
                        ],
                        (int)2 => [
                            'id' => 'db9f6a78-d445-432f-a316-c16b4c2713e3',
                            'stage_order' => (int)3,
                            'upload_type' => 'total_points',
                            'stage' => [
                                'id' => 'b3c5e972-52a2-464d-affe-71345da1906f',
                                'description' => '2025 3 LGOP'
                            ],
                            'position' => (int)1,
                            'status_code' => '0',
                            'is_nc' => false,
                            'contributory' => true,
                            'time_seconds' => (int)0,
                            'time_behind' => (int)0,
                            'points_final' => (int)100,
                            'points_behind' => null,
                            'note' => null
                        ],
                        (int)3 => [
                            'id' => 'ad812004-703b-433c-9429-41d675a946de',
                            'stage_order' => (int)4,
                            'upload_type' => 'total_points',
                            'stage' => [
                                'id' => '05fb9e04-aa48-47e8-a43d-08b9ff72a982',
                                'description' => '2025 4 LGOP'
                            ],
                            'position' => (int)1,
                            'status_code' => '0',
                            'is_nc' => false,
                            'contributory' => true,
                            'time_seconds' => (int)0,
                            'time_behind' => (int)0,
                            'points_final' => (int)100,
                            'points_behind' => null,
                            'note' => null
                        ]
                    ],
                    'overall' => [
                        'id' => '',
                        'stage_order' => (int)2,
                        'upload_type' => 'ranking_computed',
                        'stage' => null,
                        'position' => (int)1,
                        'status_code' => '0',
                        'is_nc' => null,
                        'contributory' => null,
                        'time_seconds' => (int)0,
                        'time_behind' => null,
                        'points_final' => (int)383,
                        'points_behind' => null,
                        'note' => null
                    ]
                ]
            ],
            [
                'id' => '3032451b-2e55-4305-854a-a893a8d15a4b',
                'bib_number' => '4333',
                'is_nc' => false,
                'eligibility' => null,
                'sicard' => '8022222',
                'sex' => 'F',
                'leg_number' => (int)1,
                'created' => '2025-09-24T18:38:58.372+00:00',
                'class' => [
                    'id' => '5542d502-20e1-4ed1-96db-d4287845b2b0',
                    'short_name' => 'F-14',
                    'long_name' => 'F-14'
                ],
                'club' => [
                    'id' => '40706a85-f2c3-48ad-bde6-25c0681af338',
                    'short_name' => 'GALLAECIA_RAID'
                ],
                'full_name' => 'Noelia Orozco',
                'stage' => null,
                'overalls' => [
                    'parts' => [
                        (int)0 => [
                            'id' => '5ba24a47-503f-450f-becd-cceb7c084f2b',
                            'stage_order' => (int)2,
                            'upload_type' => 'total_points',
                            'stage' => [
                                'id' => 'bdc6c03e-489b-498c-bffe-beb04311411e',
                                'description' => '2025 2 LGOP'
                            ],
                            'position' => (int)3,
                            'status_code' => '0',
                            'is_nc' => false,
                            'contributory' => true,
                            'time_seconds' => (int)0,
                            'time_behind' => (int)0,
                            'points_final' => (int)67,
                            'points_behind' => null,
                            'note' => null
                        ],
                        (int)1 => [
                            'id' => '557ee496-6d99-492c-b855-d90272c12728',
                            'stage_order' => (int)3,
                            'upload_type' => 'total_points',
                            'stage' => [
                                'id' => 'b3c5e972-52a2-464d-affe-71345da1906f',
                                'description' => '2025 3 LGOP'
                            ],
                            'position' => (int)2,
                            'status_code' => '0',
                            'is_nc' => false,
                            'contributory' => true,
                            'time_seconds' => (int)0,
                            'time_behind' => (int)0,
                            'points_final' => (int)75,
                            'points_behind' => null,
                            'note' => null
                        ],
                        (int)2 => [
                            'id' => '204b0f46-1dcf-468c-b752-e42cb3bcca48',
                            'stage_order' => (int)4,
                            'upload_type' => 'total_points',
                            'stage' => [
                                'id' => '05fb9e04-aa48-47e8-a43d-08b9ff72a982',
                                'description' => '2025 4 LGOP'
                            ],
                            'position' => (int)2,
                            'status_code' => '0',
                            'is_nc' => false,
                            'contributory' => true,
                            'time_seconds' => (int)0,
                            'time_behind' => (int)0,
                            'points_final' => (int)76,
                            'points_behind' => null,
                            'note' => null
                        ],
                        (int)3 => [
                            'id' => '716d59be-8989-4ea7-91f2-6ab7bb4c4334',
                            'stage_order' => (int)5,
                            'upload_type' => 'total_points',
                            'stage' => [
                                'id' => 'ee961500-01be-4f24-8b30-0a896e1a7626',
                                'description' => '2025 5 LGOP'
                            ],
                            'position' => (int)1,
                            'status_code' => '0',
                            'is_nc' => false,
                            'contributory' => true,
                            'time_seconds' => (int)0,
                            'time_behind' => (int)0,
                            'points_final' => (int)100,
                            'points_behind' => null,
                            'note' => null
                        ]
                    ],
                    'overall' => [
                        'id' => '',
                        'stage_order' => (int)2,
                        'upload_type' => 'ranking_computed',
                        'stage' => null,
                        'position' => (int)2,
                        'status_code' => '0',
                        'is_nc' => null,
                        'contributory' => null,
                        'time_seconds' => (int)0,
                        'time_behind' => null,
                        'points_final' => (int)318,
                        'points_behind' => null,
                        'note' => null
                    ]
                ]
            ],
            [
                'id' => '15ef805f-a96a-47e7-a3f9-5e9bb061b268',
                'bib_number' => '4999',
                'is_nc' => false,
                'eligibility' => null,
                'sicard' => '2009999',
                'sex' => 'F',
                'leg_number' => (int)1,
                'created' => '2025-09-24T18:18:22.935+00:00',
                'class' => [
                    'id' => '5542d502-20e1-4ed1-96db-d4287845b2b0',
                    'short_name' => 'F-14',
                    'long_name' => 'F-14'
                ],
                'club' => [
                    'id' => 'df1d70a7-c014-4389-8dfa-7b99ed9afa15',
                    'short_name' => 'ARTABROS'
                ],
                'full_name' => 'Elena Ponce',
                'stage' => null,
                'overalls' => [
                    'parts' => [
                        (int)0 => [
                            'id' => '4e6ae54a-e598-4173-a56f-705eeb4855f8',
                            'stage_order' => (int)1,
                            'upload_type' => 'total_points',
                            'stage' => [
                                'id' => '00e6408f-482e-44a2-b8ec-23c34af16d3b',
                                'description' => '2025 1 LGOP'
                            ],
                            'position' => (int)0,
                            'status_code' => '0',
                            'is_nc' => false,
                            'contributory' => false,
                            'time_seconds' => (int)0,
                            'time_behind' => (int)0,
                            'points_final' => (int)0,
                            'points_behind' => null,
                            'note' => null
                        ],
                        (int)1 => [
                            'id' => '8c3107c9-b02d-48df-a0ae-8a83b94250a4',
                            'stage_order' => (int)2,
                            'upload_type' => 'total_points',
                            'stage' => [
                                'id' => 'bdc6c03e-489b-498c-bffe-beb04311411e',
                                'description' => '2025 2 LGOP'
                            ],
                            'position' => (int)2,
                            'status_code' => '0',
                            'is_nc' => false,
                            'contributory' => true,
                            'time_seconds' => (int)0,
                            'time_behind' => (int)0,
                            'points_final' => (int)79,
                            'points_behind' => null,
                            'note' => null
                        ],
                        (int)2 => [
                            'id' => '160fbc41-72e0-4d8c-a468-ec5d2b21a521',
                            'stage_order' => (int)3,
                            'upload_type' => 'total_points',
                            'stage' => [
                                'id' => 'b3c5e972-52a2-464d-affe-71345da1906f',
                                'description' => '2025 3 LGOP'
                            ],
                            'position' => (int)3,
                            'status_code' => '0',
                            'is_nc' => false,
                            'contributory' => true,
                            'time_seconds' => (int)0,
                            'time_behind' => (int)0,
                            'points_final' => (int)61,
                            'points_behind' => null,
                            'note' => null
                        ],
                        (int)3 => [
                            'id' => '5757ea74-0a28-4ce9-8152-98bc85d157e9',
                            'stage_order' => (int)4,
                            'upload_type' => 'total_points',
                            'stage' => [
                                'id' => '05fb9e04-aa48-47e8-a43d-08b9ff72a982',
                                'description' => '2025 4 LGOP'
                            ],
                            'position' => (int)4,
                            'status_code' => '0',
                            'is_nc' => false,
                            'contributory' => true,
                            'time_seconds' => (int)0,
                            'time_behind' => (int)0,
                            'points_final' => (int)51,
                            'points_behind' => null,
                            'note' => null
                        ],
                        (int)4 => [
                            'id' => 'bf3435d1-68ae-4383-92be-28dc8cb79512',
                            'stage_order' => (int)5,
                            'upload_type' => 'total_points',
                            'stage' => [
                                'id' => 'ee961500-01be-4f24-8b30-0a896e1a7626',
                                'description' => '2025 5 LGOP'
                            ],
                            'position' => (int)3,
                            'status_code' => '0',
                            'is_nc' => false,
                            'contributory' => true,
                            'time_seconds' => (int)0,
                            'time_behind' => (int)0,
                            'points_final' => (int)84,
                            'points_behind' => null,
                            'note' => null
                        ]
                    ],
                    'overall' => [
                        'id' => '',
                        'stage_order' => (int)2,
                        'upload_type' => 'ranking_computed',
                        'stage' => null,
                        'position' => (int)3,
                        'status_code' => '0',
                        'is_nc' => null,
                        'contributory' => null,
                        'time_seconds' => (int)0,
                        'time_behind' => null,
                        'points_final' => (int)275,
                        'points_behind' => null,
                        'note' => null
                    ]
                ]
            ],
        ];
        return $results;
    }
}
