<?php
declare(strict_types=1);

use Migrations\AbstractSeed;
use Results\Model\Entity\Federation;

class EventsSeed extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:00');
        $data = [
            [
                'id' => 1,
                'description' => 'Test event',
                'initial_date' => '2024-01-25',
                'final_date' => '2024-01-25',
                'federation_id' => Federation::FEDO,
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
        ];

        $table = $this->table('events');
        if ($table->getAdapter()->fetchAll('SELECT * from ' . $table->getName() . ' LIMIT 1') === []) {
            $table->insert($data)->save();
        }
    }
}
