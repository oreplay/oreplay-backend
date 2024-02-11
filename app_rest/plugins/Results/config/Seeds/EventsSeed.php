<?php
declare(strict_types=1);

use Migrations\AbstractSeed;
use Results\Model\Entity\Federation;
use Results\Model\Entity\Event;

class EventsSeed extends AbstractSeed
{
    protected $seedClasses = [
        FederationsSeed::class,
    ];

    public function run(): void
    {
        foreach ($this->seedClasses as $seedClass) {
            /** @var AbstractSeed $seeder */
            $seeder = new $seedClass;
            $seeder->setAdapter($this->getAdapter());
            $seeder->run();
        }
        $now = date('Y-m-d H:i:00');
        $data = [
            [
                'id' => Event::FIRST_EVENT,
                'description' => 'Test Foot-o',
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
