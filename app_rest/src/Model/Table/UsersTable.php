<?php

namespace App\Model\Table;

use App\Lib\Consts\CacheGrp;
use App\Model\Entity\User;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Datasource\EntityInterface;
use Cake\Http\Exception\BadRequestException;
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
        return []; // $this->AdminUsers->getDependentUserIDs($uID);
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
        if (!(new DefaultPasswordHasher)->check($pass, $usr->password)) {
            throw new UnauthorizedException('Invalid password');
        }
        return $usr;
    }

    public function getUserWithNotebooks($id): User
    {
        /** @var User $user */
        $user = $this->find()
            ->where(['id' => $id])
            ->contain('Notebooks')
            ->first();
        return $user;
    }
}
