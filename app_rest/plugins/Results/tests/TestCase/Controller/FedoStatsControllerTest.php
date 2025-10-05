<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ClubsFixture;
use Results\Test\Fixture\ControlsFixture;
use Results\Test\Fixture\ControlTypesFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\SplitsFixture;

class FedoStatsControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        EventsFixture::LOAD,
        ClubsFixture::LOAD,
        ClassesFixture::LOAD,
        RunnersFixture::LOAD,
        RunnerResultsFixture::LOAD,
        SplitsFixture::LOAD,
        ControlsFixture::LOAD,
        ControlTypesFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/stages/'
            . Stage::FIRST_STAGE . '/stats/';
    }

    public function testGetList()
    {
        $paramsArray = [
            'officialSeniorM' => 'ME',
            'officialSeniorF' => 'FE',
            'officialSub20M'  => 'M18',
            'officialSub20F'  => 'F18',
        ];
        $this->get($this->_getEndpoint() . '?' . http_build_query($paramsArray));

        $bodyDecoded = $this->assertJsonResponseOK();
        $expected = [
            'officialSub20' => [
                'M' => [
                    'classes' => [],
                    'total' => 0,
                    'dns' => 0,
                    'mp' => 0,
                    'dnf' => 0,
                    'ot' => 0,
                    'dqf' => 0,
                    'notYetFinished' => 0,
                    'finished' => 0,
                    'others' => 0,
                    'otherValues' => [],
                ],
                'F' => [
                    'classes' => [],
                    'total' => 0,
                    'dns' => 0,
                    'mp' => 0,
                    'dnf' => 0,
                    'ot' => 0,
                    'dqf' => 0,
                    'notYetFinished' => 0,
                    'finished' => 0,
                    'others' => 0,
                    'otherValues' => [],
                ],
                'any' => [
                    'classes' => [],
                    'total' => 0,
                    'dns' => 0,
                    'mp' => 0,
                    'dnf' => 0,
                    'ot' => 0,
                    'dqf' => 0,
                    'notYetFinished' => 0,
                    'finished' => 0,
                    'others' => 0,
                    'otherValues' => [],
                ],
            ],
            'officialSenior' => [
                'M' => [
                    'classes' => ['ME'],
                    'total' => 1,
                    'dns' => 0,
                    'mp' => 0,
                    'dnf' => 0,
                    'ot' => 0,
                    'dqf' => 0,
                    'notYetFinished' => 0,
                    'finished' => 0,
                    'others' => 1,
                    'otherValues' => [''],
                ],
                'F' => [
                    'classes' => [],
                    'total' => 0,
                    'dns' => 0,
                    'mp' => 0,
                    'dnf' => 0,
                    'ot' => 0,
                    'dqf' => 0,
                    'notYetFinished' => 0,
                    'finished' => 0,
                    'others' => 0,
                    'otherValues' => [],
                ],
                'any' => [
                    'classes' => ['ME'],
                    'total' => 1,
                    'dns' => 0,
                    'mp' => 0,
                    'dnf' => 0,
                    'ot' => 0,
                    'dqf' => 0,
                    'notYetFinished' => 0,
                    'finished' => 0,
                    'others' => 1,
                    'otherValues' => [''],
                ],
            ],
            'others' => [
                'M' => [
                    'classes' => [],
                    'total' => 0,
                    'dns' => 0,
                    'mp' => 0,
                    'dnf' => 0,
                    'ot' => 0,
                    'dqf' => 0,
                    'notYetFinished' => 0,
                    'finished' => 0,
                    'others' => 0,
                    'otherValues' => [],
                ],
                'F' => [
                    'classes' => [],
                    'total' => 0,
                    'dns' => 0,
                    'mp' => 0,
                    'dnf' => 0,
                    'ot' => 0,
                    'dqf' => 0,
                    'notYetFinished' => 0,
                    'finished' => 0,
                    'others' => 0,
                    'otherValues' => [],
                ],
                'any' => [
                    'classes' => [],
                    'total' => 0,
                    'dns' => 0,
                    'mp' => 0,
                    'dnf' => 0,
                    'ot' => 0,
                    'dqf' => 0,
                    'notYetFinished' => 0,
                    'finished' => 0,
                    'others' => 0,
                    'otherValues' => [],
                ],
            ]
        ];
        $this->assertEquals($expected, $bodyDecoded['data']);
    }
}
