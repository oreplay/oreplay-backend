<?php

declare(strict_types = 1);

use App\Model\Table\AppTable;
use App\Test\Fixture\UsersFixture;
use Migrations\BaseSeed;

class UsersSeed extends BaseSeed
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
                'is_admin' => true,
                'is_super' => true,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'id' => 'ec8189d7-8f96-4b71-be20-9e77ff6e9e9c',
                'email' => 'beta@oreplay.es',
                'first_name' => 'Beta',
                'last_name' => 'Admin',
                'password' => '$2y$10$HLZ9RADmNnGzt3wx7o54JeaWT6zt2WIARiXDVNdFI4RIcSeLrEvs6',
                'is_admin' => false,
                'is_super' => false,
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
