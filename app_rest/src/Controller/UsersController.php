<?php

declare(strict_types = 1);

namespace App\Controller;

use App\Lib\Emails\VerifyEmail;
use App\Lib\Validator\ValidationException;
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
        $db = $this->Users->getUserByEmail($user->email);
        if ($db) {
            $user->setErrorDuplicatedEmail();
            throw new ValidationException($user);
        }
        $email = new VerifyEmail($user);
        $email->sendOrFail();
        $this->return = $user->toJsonArray();
        //$this->return['activation_url'] = $email->getCallToActionHref();
    }
}
