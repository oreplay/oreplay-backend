<?php

declare(strict_types = 1);

namespace Results\Controller;

use App\Model\Table\UsersTable;
use Results\Model\Entity\Organizer;
use Results\Model\Table\OrganizersTable;

class OrganizersManagementController extends ApiController
{
    private OrganizersTable $Organizers;

    public function isPublicController(): bool
    {
        return false;
    }

    public function initialize(): void
    {
        parent::initialize();
        $this->Organizers = OrganizersTable::load();
    }

    protected function beforeMain($id = null, $secondParam = null)
    {
        UsersTable::load()->getManagerOrFail($this->OAuthServer->getUserID());
        return null;
    }

    protected function addNew($data)
    {
        $organizer = $this->Organizers->patchFromNewWithUuid($data);
        $this->return = $this->Organizers->saveOrFail($organizer);
        $this->Organizers->deleteMatcherCache();
    }

    public function getList()
    {
        $this->return = $this->Organizers->getOrganizers();
    }

    protected function edit($id, $data)
    {
        /** @var Organizer $organizer */
        $organizer = $this->Organizers->get($id);
        unset($data['id']);
        $organizer = $this->Organizers->patchEntity($organizer, $data);
        $saved = $this->Organizers->saveOrFail($organizer);
        $this->Organizers->deleteMatcherCache();
        $this->return = $this->Organizers->get($saved->id);
    }

    protected function delete($id)
    {
        $organizer = $this->Organizers->get($id);
        $this->Organizers->softDelete($organizer->id);
        $this->Organizers->deleteMatcherCache();
        $this->return = false;
    }
}
