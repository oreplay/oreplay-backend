<?php
declare(strict_types=1);

use Migrations\AbstractSeed;
use Results\Model\Entity\Federation;

class FederationsSeed extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:00');
        $data = [
            [
                'id' => Federation::FEDO,
                'description' => 'FEDO SICO',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => 'IOF',
                'description' => 'IOF OEVENTOR',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
        ];

        $table = $this->table('federations');
        if ($table->getAdapter()->fetchAll('SELECT * from ' . $table->getName() . ' LIMIT 1') === []) {
            $table->insert($data)->save();
        }
    }
}
