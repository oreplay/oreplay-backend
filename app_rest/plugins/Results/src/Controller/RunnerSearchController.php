<?php

declare(strict_types = 1);

namespace Results\Controller;

use App\Model\Table\UsersTable;
use Cake\Http\Exception\BadRequestException;
use Results\Model\Entity\Runner;
use Results\Model\Table\RunnersTable;

class RunnerSearchController extends ApiController
{
    private RunnersTable $Runners;

    public function isPublicController(): bool
    {
        return false;
    }

    public function initialize(): void
    {
        parent::initialize();
        $this->Runners = RunnersTable::load();
    }

    protected function beforeMain($id = null, $secondParam = null)
    {
        UsersTable::load()->getManagerOrFail($this->OAuthServer->getUserID());
        return null;
    }

    protected function getList()
    {
        $text = trim((string)$this->request->getQuery('text'));
        if ($text === '') {
            throw new BadRequestException('Mandatory query param text');
        }
        $runners = $this->Runners->searchByName(
            $text,
            $this->request->getQuery('event_id'),
            $this->request->getQuery('stage_id')
        );
        $this->return = array_map(function (Runner $runner) {
            return $runner->toSearchArray();
        }, $runners);
    }
}
