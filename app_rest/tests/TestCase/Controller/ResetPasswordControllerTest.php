<?php

declare(strict_types = 1);

namespace App\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Lib\Consts\CacheGrp;
use App\Lib\Emails\EmailBase;
use App\Lib\ResetPasswordCode;
use App\Model\Table\UsersTable;
use App\Test\Fixture\UsersFixture;
use Cake\Cache\Cache;

class ResetPasswordControllerTest extends ApiCommonErrorsTest
{
    protected array $fixtures = [
        'app.Users'
    ];

    public function setUp(): void
    {
        $this->_writeCacheAdminCode('');
        parent::setUp();
    }

    private function _writeCacheAdminCode(string $secret): void
    {
        Cache::write(ResetPasswordCode::CACHE_KEY . UsersFixture::USER_ADMIN_ID, $secret, CacheGrp::SHORT);
    }

    private function _readCacheAdminCode(): ?string
    {
        return Cache::read(ResetPasswordCode::CACHE_KEY . UsersFixture::USER_ADMIN_ID, CacheGrp::SHORT);
    }

    private function _prepareAdminWithCode(string $code): string
    {
        $UsersTable = UsersTable::load();
        $UsersTable->updateAll(['email' => EmailBase::SKIP_SEND_EMAIL_ADDRESS], ['id' => UsersFixture::USER_ADMIN_ID]);
        $this->_writeCacheAdminCode($code);
        return $UsersTable->get(UsersFixture::USER_ADMIN_ID)->password;
    }

    private function _patchPassword(string $code): void
    {
        $this->skipNextRequestInSwagger();
        $this->patch($this->_getEndpoint() . $code, [
            'email' => EmailBase::SKIP_SEND_EMAIL_ADDRESS,
            'password' => 'Test6854pass',
        ]);
    }

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/resetPassword/';
    }

    public function testAddNew()
    {
        UsersTable::load()->updateAll(['email' => EmailBase::SKIP_SEND_EMAIL_ADDRESS], ['id' => UsersFixture::USER_ADMIN_ID]);
        $data = [
            'email'=> EmailBase::SKIP_SEND_EMAIL_ADDRESS,
        ];

        $this->post($this->_getEndpoint(), $data);

        $this->assertResponseOk($this->_getBodyAsString());
        $this->assertEquals(204, $this->_response->getStatusCode());

        $code = $this->_readCacheAdminCode();
        $this->assertEquals(6, strlen($code));
    }

    public function testEdit()
    {
        $UsersTable = UsersTable::load();
        $UsersTable->updateAll(['email' => EmailBase::SKIP_SEND_EMAIL_ADDRESS], ['id' => UsersFixture::USER_ADMIN_ID]);
        $admin = $UsersTable->get(UsersFixture::USER_ADMIN_ID);

        $passwordCode = '222333';
        $this->_writeCacheAdminCode($passwordCode);
        $data = [
            'email'=> EmailBase::SKIP_SEND_EMAIL_ADDRESS,
            'password'=> 'Test6854pass',
        ];
        $this->patch($this->_getEndpoint() . $passwordCode, $data);

        $user = $this->assertJsonResponseOK()['data'];
        $usr = $UsersTable->checkLogin(['username' => EmailBase::SKIP_SEND_EMAIL_ADDRESS, 'password' => $data['password']]);
        $this->assertNotEquals($admin->password, $usr->password);
        $this->assertStringStartsWith('$2y$10$', $usr->password);

    }

    public function testEditWithWrongCodeIsRejected()
    {
        $UsersTable = UsersTable::load();
        $originalPassword = $this->_prepareAdminWithCode('222333');

        $this->_patchPassword('999999');

        $this->assertException('Bad Request', 400, 'Invalid code');
        $this->assertEquals($originalPassword, $UsersTable->get(UsersFixture::USER_ADMIN_ID)->password);
    }

    public function testEditWithUsedCodeIsRejected()
    {
        $passwordCode = '222333';
        $this->_prepareAdminWithCode($passwordCode);
        $this->_patchPassword($passwordCode);
        $this->assertJsonResponseOK();

        $this->_patchPassword($passwordCode);

        $this->assertException('Bad Request', 400, 'Invalid code');
    }

    public function testEditWithTooManyWrongCodesInvalidatesTheCode()
    {
        $passwordCode = '222333';
        $originalPassword = $this->_prepareAdminWithCode($passwordCode);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->_patchPassword('999999');
            $this->assertException('Bad Request', 400, 'Invalid code');
        }

        $this->_patchPassword($passwordCode);

        $this->assertException('Bad Request', 400, 'Invalid code');
        $this->assertEquals($originalPassword, UsersTable::load()->get(UsersFixture::USER_ADMIN_ID)->password);
    }
}
