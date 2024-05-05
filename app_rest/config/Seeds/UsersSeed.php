<?php

declare(strict_types = 1);

use App\Model\Table\AppTable;
use App\Test\Fixture\UsersFixture;
use Migrations\AbstractSeed;

class UsersSeed extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:00');
        $data = [
            [
                'id' => UsersFixture::USER_ADMIN_ID,
                'email' => 'admin@example.com',
                'first_name' => 'Admin',
                'last_name' => 'Admin',
                'password' => '$2y$10$HLZ9RADmNnGzt3wx7o54JeaWT6zt2WIARiXDVNdFI4RIcSeLrEvs6',
                'created' => $now,
                'modified' => $now,
            ],
        ];

        $table = $this->table(AppTable::TABLE_PREFIX . 'users');
        if ($table->getAdapter()->fetchAll('SELECT * from ' . $table->getName() . ' LIMIT 1') === []) {
            $table->insert($data)->save();
        }
    }
}
