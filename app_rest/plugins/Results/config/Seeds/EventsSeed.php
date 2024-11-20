<?php

declare(strict_types = 1);

use Migrations\AbstractSeed;
use Results\Model\Entity\Federation;
use Results\Model\Entity\Event;

class EventsSeed extends AbstractSeed
{
    protected $seedClasses = [
        FederationsSeed::class,
        OrganizersSeed::class,
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
                'organizer_id' => '1',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => Event::SECOND_EVENT,
                'description' => 'Test Foot-o Future Event',
                'initial_date' => '2025-01-01',
                'final_date' => '2025-12-31',
                'federation_id' => Federation::FEDO,
                'organizer_id' => '1',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => Event::THIRD_EVENT,
                'description' => 'Test Foot-o Present Event 1',
                'initial_date' => '2024-01-01',
                'final_date' => '2024-12-31',
                'federation_id' => Federation::FEDO,
                'organizer_id' => '1',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => Event::FOURTH_EVENT,
                'description' => 'Test Foot-o Present Event 2',
                'initial_date' => '2024-01-02',
                'final_date' => '2024-12-31',
                'federation_id' => Federation::FEDO,
                'organizer_id' => '1',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => '8fdr542c-23b9-4790-a117-b83Af4760ad9',
                'description' => 'Test Foot-o Present Event 3',
                'initial_date' => '2024-01-02',
                'final_date' => '2024-12-31',
                'federation_id' => Federation::FEDO,
                'organizer_id' => '1',
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
