<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ClubsFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\TeamsFixture;

class ResultsByClassControllerTest extends ApiCommonErrorsTest
{
    protected array $fixtures = [
        EventsFixture::LOAD,
        ClubsFixture::LOAD,
        ClassesFixture::LOAD,
        RunnersFixture::LOAD,
        TeamsFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/stages/'
            . Stage::FIRST_STAGE . '/resultsByClass/';
    }

    public function testGetList()
    {
        $this->get($this->_getEndpoint() . '?output=ReadablePointsCsv&contrib_text=(contrib.)');

        $body = $this->_getBodyAsString();
        $expectedCsv = '
ME
;First Team;Club A;;
;First Runner;Club A;;
';
        $this->assertEquals($expectedCsv, $body);
    }
}
