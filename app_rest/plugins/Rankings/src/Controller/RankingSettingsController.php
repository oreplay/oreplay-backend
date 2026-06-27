<?php

declare(strict_types = 1);

namespace Rankings\Controller;

use App\Lib\FullBaseUrl;
use App\Model\Table\UsersTable;
use Rankings\Model\Entity\Ranking;
use Rankings\Model\Table\RankingsTable;
use RestApi\Lib\Helpers\PaginationHelper;
use Results\Controller\ApiController;

class RankingSettingsController extends ApiController
{
    private RankingsTable $Rankings;

    public function isPublicController(): bool
    {
        return false;
    }

    public function initialize(): void
    {
        parent::initialize();
        $this->Rankings = RankingsTable::load();
    }

    protected function beforeMain($id = null, $secondParam = null)
    {
        UsersTable::load()->getManagerOrFail($this->OAuthServer->getUserID());
        return null;
    }

    protected function getList()
    {
        $paginator = new PaginationHelper($this->request);
        $query = $this->Rankings->find()->orderByDesc('created');

        $this->flatResponse = true;
        $this->return = $paginator->getReturnArray($query, FullBaseUrl::host());
    }

    protected function getData($id)
    {
        $this->return = $this->Rankings->get($id);
    }

    protected function addNew($data)
    {
        $ranking = $this->Rankings->patchFromNewWithUuid($data);
        $this->return = $this->Rankings->saveOrFail($ranking);
    }

    protected function edit($id, $data)
    {
        /** @var Ranking $ranking */
        $ranking = $this->Rankings->get($id);
        $oldStageId = $ranking->getStageId();
        unset($data['id']);
        $ranking = $this->Rankings->patchEntity($ranking, $data);
        $saved = $this->Rankings->saveOrFail($ranking);
        $this->Rankings->deleteCache($id);
        $this->Rankings->deleteCacheByStage($oldStageId);
        $this->Rankings->deleteCacheByStage($saved->getStageId());
        $this->return = $this->Rankings->get($saved->id);
    }

    protected function put($id, $data)
    {
        $this->edit($id, $data);
    }

    protected function delete($id)
    {
        /** @var Ranking $ranking */
        $ranking = $this->Rankings->get($id);
        $this->Rankings->softDelete($id);
        $this->Rankings->deleteCache($id);
        $this->Rankings->deleteCacheByStage($ranking->getStageId());
        $this->return = false;
    }
}
