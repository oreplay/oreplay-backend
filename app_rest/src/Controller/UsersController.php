<?php

declare(strict_types = 1);

namespace App\Controller;

use App\Lib\Emails\VerifyEmail;
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

    protected function addNew($data)
    {
        /** @var User $user */
        $user = $this->Users->newEmptyEntity();
        $user = $this->Users->patchEntity($user, $data);
        $email = new VerifyEmail($user);
        $email->sendOrFail();
        $this->return = $user->toJsonArray();
    }
}
