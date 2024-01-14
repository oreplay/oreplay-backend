<?php
declare(strict_types=1);

use App\Model\Table\AppTable;
use Migrations\AbstractSeed;

class UsersSeed extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:00');
        $data = [
            [
                'id' => 1,
                'email' => 'admin@example.com',
                'firstname' => 'Admin',
                'lastname' => 'Admin',
                'password' => '$2y$10$HLZ9RADmNnGzt3wx7o54JeaWT6zt2WIARiXDVNdFI4RIcSeLrEvs6',
                'group_id' => 1,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'id' => 2,
                'email' => 'seller@example.com',
                'firstname' => 'Seller',
                'lastname' => 'Test',
                'password' => '$2y$10$HLZ9RADmNnGzt3wx7o54JeaWT6zt2WIARiXDVNdFI4RIcSeLrEvs6',
                'group_id' => 4,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'id' => 3,
                'email' => 'user@example.com',
                'firstname' => 'User',
                'lastname' => 'Test',
                'password' => '$2y$10$HLZ9RADmNnGzt3wx7o54JeaWT6zt2WIARiXDVNdFI4RIcSeLrEvs6',
                'group_id' => 3,
                'created' => $now,
                'modified' => $now,
            ],
        ];

        $table = $this->table(AppTable::TABLE_PREFIX . 'users');
        $table->insert($data)->save();
    }
}
