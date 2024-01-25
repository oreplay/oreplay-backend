<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use App\Lib\Consts\UserGroups;
use RestApi\TestSuite\Fixture\RestApiFixture;

class UsersFixture extends RestApiFixture
{
    const LOAD = 'app.Users';
    const SELLER_ID = 2;

    public $records = [
        [
            'id' => self::SELLER_ID,
            'email' => 'seller@example.com',
            'first_name' => 'My Name',
            'last_name' => 'My Surname',
            'password' => '$2y$10$1cCayk8qquFFWyvk161qZuOm4kgLFbmg4O1ItVQ5Qt.w3V28VNUk2',
            'created' => '2021-01-18 10:39:23',
            'modified' => '2021-01-18 10:41:31'
        ],
    ];
}
