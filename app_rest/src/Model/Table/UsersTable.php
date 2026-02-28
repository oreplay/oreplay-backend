<?php

declare(strict_types = 1);

namespace App\Model\Table;

use App\Lib\Consts\CacheGrp;
use App\Model\Entity\User;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Datasource\EntityInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\UnauthorizedException;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\Utility\Text;

class UsersTable extends AppTable
{
    public function __construct(array $config = [])
    {
        $this->_table = env('USERS_TABLE', 'users');
        parent::__construct($config);
    }

    public static function load(): self
    {
        /** @var UsersTable $table */
        $table = parent::load();
        return $table;
    }

    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
    }

    public function newEmptyEntity(): EntityInterface
    {
        $res = parent::newEmptyEntity();
        $res->id = Text::uuid();
        return $res;
    }

    public function getDependentUserIDs($uID): array
    {
        return []; // maybe revert to $this->AdminUsers->getDependentUserIDs($uID);
    }

    public function getUserByEmail(string $email)
    {
        return $this->find()->where(['email' => $email])->first();
    }

    public function getUserByEmailOrNew(array $data)
    {
        $usr = $this->getUserByEmail($data['email']);
        if (!$usr) {
            $usr = $this->newEmptyEntity();
            $usr = $this->patchEntity($usr, $data);
            $usr->is_admin = false;
            $usr->is_super = false;
        }
        return $usr;
    }

    private function _getFirst($uid): User
    {
        return $this->findById($uid)
            ->cache('_getFirst' . $uid, CacheGrp::EXTRALONG)
            ->firstOrFail();
    }

    public function getUserGroup($uid): ?int
    {
        $u = $this->_getFirst($uid);
        return $u->group_id ?? null;
    }

    public function checkLogin(array $data)
    {
        $email = $data['username'] ?? '';
        if (!$email) {
            throw new BadRequestException('Username is required');
        }
        $pass = $data['password'] ?? '';
        if (!$pass) {
            throw new BadRequestException('Password is required');
        }
        /** @var User $usr */
        $usr = $this->find()
            ->where(['email' => $email])
            ->first();
        if (!$usr) {
            throw new UnauthorizedException('User not found ' . $email);
        }
        if (!(new DefaultPasswordHasher())->check($pass, $usr->password)) {
            throw new UnauthorizedException('Invalid password');
        }
        return $usr;
    }

    public function getManagerOrFail(string $uid): User
    {
        /** @var User $user */
        $user = $this->_getFirst($uid);
        if (!$user->isManager()) {
            throw new ForbiddenException('Only for manager users');
        }
        return $user;
    }

    public function createSimpleUser(array $usr, string $pass = null): User
    {
        unset($usr['id']);
        /** @var User $user */
        $user = $this->newEmptyEntity();
        $user = $this->patchEntity($user, $usr);
        if ($pass) {
            $user->password = $pass;
        }
        $user->is_admin = false;
        $user->is_super = false;
        $saved = $this->saveOrFail($user);
        /** @var User $ret */
        $ret = $this->get($saved->id);
        return $ret;
    }
}
