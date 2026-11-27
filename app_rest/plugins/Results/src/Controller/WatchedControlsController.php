<?php

declare(strict_types = 1);

namespace Results\Controller;

use Results\Lib\Publish\WatchedControls;

class WatchedControlsController extends ApiController
{
    public function isPublicController(): bool
    {
        return true;
    }

    protected function getList()
    {
        $stageId = $this->request->getParam('stageID');
        $station = $this->request->getParam('station');
        (new WatchedControls())->watch($stageId, $station);
        $this->return = [];
    }
}
