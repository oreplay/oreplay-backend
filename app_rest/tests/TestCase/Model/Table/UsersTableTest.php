<?php
namespace App\Test\TestCase\View\Helper;

use App\Lib\Consts\CacheGrp;
use App\Lib\Consts\UserGroups;
use App\Model\Table\UsersTable;
use App\Test\Fixture\UsersFixture;
use Cake\Cache\Cache;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\UnauthorizedException;
use Cake\TestSuite\Fixture\FixtureStrategyInterface;
use Cake\TestSuite\Fixture\TransactionStrategy;
use Cake\TestSuite\TestCase;

class UsersTableTest extends TestCase
{
    protected $fixtures = [
        UsersFixture::LOAD
    ];

    protected function getFixtureStrategy(): FixtureStrategyInterface
    {
        return new TransactionStrategy();
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->Users = UsersTable::load();
    }
    public function testCheckLogin_withEmptyArray(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Username is required');
        $this->Users->checkLogin([]);
    }

    public function testCheckLogin_withoutPassword(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Password is required');
        $this->Users->checkLogin(['username' => 'fake']);
    }

    public function testCheckLogin_withNonExistingEmail(): void
    {
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('User not found');
        $this->Users->checkLogin(['username' => 'fake', 'password' => 'f']);
    }

    public function testCheckLogin_withWrongPassword(): void
    {
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Invalid password');
        $data = [
            'username' => UsersFixture::USER_ADMIN_EMAIL,
            'password' => 'invalidpass',
        ];
        $this->Users->checkLogin($data);
    }

    public function testCheckLogin(): void
    {
        $data = [
            'username' => UsersFixture::USER_ADMIN_EMAIL,
            'password' => 'passpass',
        ];
        $res = $this->Users->checkLogin($data);
        $this->assertEquals($data['username'], $res->email);
    }
}
