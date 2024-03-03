<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;
use Results\Model\Entity\Club;

/**
 * @property RunnersTable $Runner
 */
class ClubsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        RunnersTable::addBelongsTo($this);
    }

    public function createIfNotExists(string $eventId, string $stageId, array $data): Club
    {
        $club = $this->find()
            ->where([
                'event_id' => $eventId,
                'stage_id' => $stageId,
                'short_name' => $data['short_name']
            ])
            ->first();
        if (!$club) {
            /** @var Club $club */
            $club = $this->patchFromNewWithUuid($data);
            $club->event_id = $eventId;
            $club->stage_id = $stageId;
        }
        return $club;
    }
}
