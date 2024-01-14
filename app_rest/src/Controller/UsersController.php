<?php

namespace App\Controller;

use App\Model\Entity\User;
use App\Model\Table\UsersTable;

/**
 * @property UsersTable $Users
 */
class UsersController extends ApiController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->Users = UsersTable::load();
    }

    public function isPublicController(): bool
    {
        return true;
    }

    protected function getMandatoryParams(): array
    {
        return [];
    }

    protected function addNew($data)
    {
        /** @var User $user */
        $user = $this->Users->newEmptyEntity();
        $user = $this->Users->patchEntity($user, $data);
        $user->group_id = 5;
        $saved = $this->Users->saveOrFail($user);

        $this->return = $this->Users->get($saved->id);
    }
}
