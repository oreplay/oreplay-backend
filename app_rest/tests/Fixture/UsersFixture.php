<?php

declare(strict_types = 1);

namespace App\Test\Fixture;

use App\Lib\Consts\SeedIds;
use RestApi\TestSuite\Fixture\RestApiFixture;

class UsersFixture extends RestApiFixture
{
    const LOAD = 'app.Users';
    const USER_ADMIN_ID = SeedIds::USER_ADMIN_ID;
    const USER_ADMIN_EMAIL = SeedIds::USER_ADMIN_EMAIL;
    const USER_NON_ADMIN_ID = '54';
    const USER_NON_ADMIN_EMAIL = 'user@example.com';

    public array $records = [
        [
            'id' => self::USER_ADMIN_ID,
            'email' => self::USER_ADMIN_EMAIL,
            'first_name' => 'My Name',
            'last_name' => 'My Surname',
            'is_admin' => true,
            'password' => '$2y$10$1cCayk8qquFFWyvk161qZuOm4kgLFbmg4O1ItVQ5Qt.w3V28VNUk2',
            'created' => '2021-01-18 10:39:23',
            'modified' => '2021-01-18 10:41:31'
        ],
        [
            'id' => self::USER_NON_ADMIN_ID,
            'email' => self::USER_NON_ADMIN_EMAIL,
            'first_name' => 'Non',
            'last_name' => 'Admin',
            'is_admin' => false,
            'password' => '$2y$10$1cCayk8qquFFWyvk161qZuOm4kgLFbmg4O1ItVQ5Qt.w3V28VNUk2',
            'created' => '2021-01-18 10:39:23',
            'modified' => '2021-01-18 10:41:31'
        ],
    ];
}
