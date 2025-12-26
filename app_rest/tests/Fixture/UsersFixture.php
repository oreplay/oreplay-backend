<?php

declare(strict_types = 1);

namespace App\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;

class UsersFixture extends RestApiFixture
{
    const LOAD = 'app.Users';
    const USER_ADMIN_ID = '8186ef35-e8c1-4e5c-bcc4-42bb362f050b';
    const USER_ADMIN_EMAIL = 'admin@example.com';

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
    ];
}
