<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Table\UsersTable;
use Cake\Http\Exception\ForbiddenException;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query;
use Results\Model\Entity\Event;

/**
 * @property FederationsTable $Federations
 * @property UsersTable $Users
 * @property StagesTable $Stages
 */
class EventsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        FederationsTable::addHasMany($this);
        StagesTable::addBelongsTo($this);
        $this->belongsToMany('Users', [
            'joinTable' => 'users_events',
        ]);
    }

    public function findPaginatedEvents(array $filters): Query
    {
        return $this->find()->orderAsc('created');
    }

    public function getEventWithRelations(string $id): Event
    {
        $query = $this->find()
            ->contain(FederationsTable::name())
            ->contain(StagesTable::name())
            ->where(['Events.id' => $id]);
        /** @var Event $res */
        $res = $query->firstOrFail();
        return $res;
    }

    public function getEventFromUser(string $eventId, string $userId): Event
    {
        /** @var Event $event */
        $event = $this->find()
            ->where(['id' => $eventId])
            ->contain(UsersTable::name(), function (Query $query) use ($userId) {
                return $query->where([
                    'Users.id' => $userId,
                ]);
            })
            ->firstOrFail();
        if (!$event->getFirstUser()) {
            throw new ForbiddenException('Event not from this user');
        }
        return $event;
    }
}
