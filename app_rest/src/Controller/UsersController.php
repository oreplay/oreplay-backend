<?php

declare(strict_types = 1);

namespace App\Controller;

use App\Model\Entity\User;
use App\Model\Table\UsersTable;

class UsersController extends ApiController
{
    private UsersTable $Users;

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
        $user->is_admin = false;
        $user->is_super = false;
        $saved = $this->Users->saveOrFail($user);

        $this->return = $this->Users->get($saved->id);
    }
}
