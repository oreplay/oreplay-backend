<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Controller\EventsController;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Event;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Runner;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\CoursesTable;
use Results\Model\Table\RunnersTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ClubsFixture;
use Results\Test\Fixture\ControlsFixture;
use Results\Test\Fixture\ControlTypesFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\ResultTypesFixture;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\SplitsFixture;
use Results\Test\Fixture\StagesFixture;

class UploadsControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        ClubsFixture::LOAD,
        ClassesFixture::LOAD,
        RunnersFixture::LOAD,
        ResultTypesFixture::LOAD,
        RunnerResultsFixture::LOAD,
        SplitsFixture::LOAD,
        ControlsFixture::LOAD,
        ControlTypesFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/uploads/';
    }

    public function testAddNew_shouldAddStartDates()
    {
        $this->loadAuthToken(EventsController::FAKE_TOKEN);
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = ['oreplay_data_transfer' => $this->_exampleImport()];
        $this->post($this->_getEndpoint(), $data);

        $jsonDecoded = $this->assertJsonResponseOK()['data'];

        $addedClasses = $ClassesTable->find()
            ->where(['Classes.stage_id' => StagesFixture::STAGE_FEDO_2])
            ->contain(CoursesTable::name())
            ->orderAsc('Classes.oe_key')
            ->all();
        $this->assertEquals(2, count($addedClasses));
        $expectedClasses = ['ME', 'WE'];
        foreach ($addedClasses as $k => $class) {
            $this->assertEquals($expectedClasses[$k], $class->short_name);
            $this->assertEquals($expectedClasses[$k], $class->course->short_name);
        }

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->orderAsc('last_name')
            ->all();

        $this->assertEquals(4, count($res), 'Runner count in db');
        $this->assertEquals(2, count($jsonDecoded[0]['runners']));
        $this->assertEquals(2, count($jsonDecoded[1]['runners']));
        $runnersJson = array_merge($jsonDecoded[0]['runners'], $jsonDecoded[1]['runners']);
        /** @var Runner $value */
        foreach ($res as $key => $value) {
            $this->assertEquals($runnersJson[$key]['last_name'], $value->last_name);
            $this->assertEquals($runnersJson[$key]['first_name'], $value->first_name);
            $this->assertEquals($runnersJson[$key]['sicard'], $value->sicard);
            $this->assertEquals($runnersJson[$key]['bib_number'], $value->bib_number);
            $this->assertEquals($runnersJson[$key]['id'], $value->id);
            $this->assertEquals($runnersJson[$key]['club']['short_name'], $value->club->short_name);
            $this->assertEquals($runnersJson[$key]['runner_results'][0]['start_time'],
                $value->runner_results[0]->start_time->jsonSerialize());
            if ($key === 0) {
                $this->assertEquals('2014-07-06T13:09:01.523+00:00', $runnersJson[$key]['runner_results'][0]['start_time']);
            }
            $this->assertEquals($runnersJson[$key]['runner_results'][0]['id'],
                $value->runner_results[0]->id);
            $this->assertEquals(ResultType::STAGE,
                $value->runner_results[0]->result_type_id);
        }
    }

    public function testAddNew_shouldRequireAuthenticatedToken()
    {
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = ['oreplay_data_transfer' => $this->_exampleImport()];
        $this->post($this->_getEndpoint(), $data);
        $this->assertException('Forbidden', 403, 'Invalid Bearer token');
    }

    private function _exampleImport(): array
    {
        return [
            'configuration' => [
                'source' => 'OE2010',
                'iof_version' => '3.0',
            ],
            'event' => [
                'id' => '8f3b542c-23b9-4790-a113-b83d476c0ad9',
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
                                            'oe_key' => '21715',
                                            'short_name' => 'Bredaryds SOK',
                                            'long_name' => 'Bredaryds SOK'
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
                                            'oe_key' => '28026',
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
                                            'oe_key' => '30303',
                                            'short_name' => 'Hana Orienteering',
                                            'long_name' => 'Hana Orienteering'
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
