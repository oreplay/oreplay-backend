<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use App\Lib\Consts\UserGroups;
use RestApi\TestSuite\Fixture\RestApiFixture;

class UsersFixture extends RestApiFixture
{
    const LOAD = 'app.Users';
    const ADMIN_ID = 1;
    const SELLER_ID = 2;
    const BUYER_ID = 3;

    public $records = [
        [
            'id' => self::SELLER_ID,
            'email' => 'seller@example.com',
            'firstname' => 'My Name',
            'lastname' => 'My Surname',
            'password' => '$2y$10$1cCayk8qquFFWyvk161qZuOm4kgLFbmg4O1ItVQ5Qt.w3V28VNUk2',
            'group_id' => UserGroups::SELLER,
            'created' => '2021-01-18 10:39:23',
            'modified' => '2021-01-18 10:41:31'
        ],
        [
            'id' => self::BUYER_ID,
            'email' => 'buyer@example.com',
            'firstname' => 'Regular',
            'lastname' => 'User',
            'password' => '$2y$10$1cCayk8qquFFWyvk161qZuOm4kgLFbmg4O1ItVQ5Qt.w3V28VNUk2',
            'group_id' => UserGroups::BUYER,
            'created' => '2021-03-02 10:00:00',
            'modified' => '2021-03-02 10:00:00'
        ],
        [
            'id' => self::ADMIN_ID,
            'email' => 'admin@example.com',
            'firstname' => 'Admi',
            'lastname' => 'Nistrator',
            'password' => '$2y$10$1cCayk8qquFFWyvk161qZuOm4kgLFbmg4O1ItVQ5Qt.w3V28VNUk2',
            'group_id' => UserGroups::ADMIN,
            'created' => '2021-03-02 10:00:00',
            'modified' => '2021-03-02 10:00:00'
        ],
        [
            'id' => 4,
            'email' => 'anotherbuyer@example.com',
            'firstname' => 'Another',
            'lastname' => 'Buyer',
            'password' => '$2y$10$1cCayk8qquFFWyvk161qZuOm4kgLFbmg4O1ItVQ5Qt.w3V28VNUk2',
            'group_id' => UserGroups::BUYER,
            'created' => '2021-03-02 10:00:00',
            'modified' => '2021-03-02 10:00:00'
        ],
    ];
}
