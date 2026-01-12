<?php

declare(strict_types = 1);

namespace App\Controller;

use App\Lib\Consts\NotificationTypes;
use App\Lib\Emails\VerifyEmail;
use App\Lib\FullBaseUrl;
use App\Model\Table\UsersTable;
use Cake\Http\Exception\BadRequestException;

class ValidateTokensController extends ApiController
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

    protected function getList()
    {
        $token = $this->getRequest()->getQuery('token');
        $this->addNew(['token' => $token]);

        $accepts = $this->getRequest()->getHeader('Accept')[0] ?? null;
        if (!str_contains($accepts, 'application/json')) {
            $this->redirect(FullBaseUrl::host() . '/signin');
        }
    }

    protected function addNew($data)
    {
        $token = $data['token'] ?? null;
        if (!$token) {
            throw new BadRequestException('Token is required');
        }
        $jwt = VerifyEmail::decryptToken($token);
        switch ($jwt['token_type'] ?? '') {
            case NotificationTypes::VERIFY_ADDRESS:
                $this->return = $this->Users->createSimpleUser($jwt);
                break;
            default:
                throw new BadRequestException('Invalid token action');
        }
    }
}
