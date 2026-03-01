<?php

declare(strict_types = 1);

namespace App\Controller;

use App\Lib\Emails\ResetPassword;
use App\Lib\Emails\VeEer;
use App\Model\Table\UsersTable;
use Cake\Http\Exception\BadRequestException;

class ResetPasswordController extends ApiController
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
        $emailAddress = $data['email'] ?? null;
        if (!$emailAddress) {
            throw new BadRequestException('Email is required');
        }
        $user = $this->Users->getUserByEmail($emailAddress);
        if ($user) {
            $email = new ResetPassword($user);
            $email->sendOrFail();
        }
        $this->return = false;
    }

    protected function edit($id, $data)
    {
        $code = $id;
        $emailAddress = $data['email'] ?? null;
        if (!$emailAddress) {
            throw new BadRequestException('Email is required');
        }
        $password = $data['password'] ?? null;
        if (!$password) {
            throw new BadRequestException('Password is required');
        }
        $user = $this->Users->getUserByEmail($emailAddress);
        if (!$user) {
            throw new BadRequestException('Email address does not exist');
        }
        $user->password = $password;
        $this->Users->saveOrFail($user);

        $this->return = $user;
    }
}
