<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Table\UsersTable;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query;
use DateTime;
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

    /**
     * Generate a paginated events query with a given set of filters
     * @param array $filters Array of query filters
     * @return Query With the selected events
     * @throws BadRequestException If a filter value is not within allowed range
     */
    public function findPaginatedEvents(array $filters): Query
    {
        $today = new DateTime('now');
        $today = $today->format('Y-m-d');

        $query = $this->find();

        //Filter by ?when='today','past',future
        if (array_key_exists('when', $filters)) {
            $when = $filters['when'];
            // case today
            if ($when === 'today') {
                $query = $query->where([
                    'initial_date <=' => $today,
                    'final_date >=' => $today
                ]);
            // case past
            } elseif ($when === 'past') {
                $query = $query->where([
                    'final_date <' => $today
                ]);
            // case future
            } elseif ($when === 'future') {
                $query = $query->where([
                    'initial_date >' => $today,
                ]);
            // sorry man, it was not meant to be
            } else {
                throw new BadRequestException("?when must be either null or a literal 'today', 'future, or 'past'.");
            }
        }

        // Return query
        return $query->orderAsc('created');
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
