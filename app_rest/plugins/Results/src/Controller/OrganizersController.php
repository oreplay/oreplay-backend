<?php

declare(strict_types = 1);

namespace Results\Controller;

use Results\Model\Table\OrganizersTable;

/**
 * @property OrganizersTable $Organizers
 */
class OrganizersController extends ApiController
{
    public function isPublicController(): bool
    {
        return true;
    }

    public function getList()
    {
        $this->return = $this->Organizers->find()
            ->orderAsc('name')->all();
    }
}
