<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Cake\Utility\Text;
use Results\Lib\Publish\WatchedControls;

class WatchedControlsControllerTest extends ApiCommonErrorsTest
{
    private string $stageId;

    public function setUp(): void
    {
        parent::setUp();
        $this->stageId = Text::uuid();
    }

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/internal/stages/' . $this->stageId . '/controls/31/watched/';
    }

    public function testGetList_marksTheControlAsWatched()
    {
        $this->skipNextRequestInSwagger();
        $this->get($this->_getEndpoint());

        $this->assertResponseOk();
        $this->assertSame(['31'], (new WatchedControls())->watchedAmong($this->stageId, ['31', '45']));
    }
}
