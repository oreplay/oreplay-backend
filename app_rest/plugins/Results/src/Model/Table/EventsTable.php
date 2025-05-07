<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Table\UsersTable;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use DateTime;
use RestApi\Lib\Exception\DetailedException;
use Results\Model\Entity\Event;

/**
 * @property FederationsTable $Federations
 * @property OrganizersTable $Organizers
 * @property UsersTable $Users
 * @property StagesTable $Stages
 */
class EventsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        FederationsTable::addHasMany($this);
        OrganizersTable::addHasMany($this);
        StagesTable::addBelongsTo($this)
            ->setSort([StagesTable::field('created') => 'ASC']);
        $this->belongsToMany(UsersTable::name(), [
            'joinTable' => 'users_events',
        ]);
    }

    public static function load(): self
    {
        /** @var EventsTable $table */
        $table = parent::load();
        return $table;
    }

    public function patchFromNewValidatingFederation(array $data): Event
    {
        /** @var Event $event */
        $event = $this->patchFromNewWithUuid($data);
        if ($data['federation_id'] ?? null) {
            $federation = $this->Federations->findById($data['federation_id'])->first();
            if (!$federation) {
                throw new DetailedException('federation_id could not be found, contact support');
            }
            $event->federation_id = $data['federation_id'];
        }
        return $event;
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

        $isShowHiddenOff = ($filters['show_hidden'] ?? null) !== '1';
        if ($isShowHiddenOff) {
            $query->where(['is_hidden' => false]);
        }

        $when = $filters['when'] ?? null;
        //Filter by ?when='today','past',future
        if ($when) {
            // case today
            if ($when === 'today') {
                $filters['initial_date:lte'] = $today;
                $filters['final_date:gte'] = $today;
                // case past
            } elseif ($when === 'past') {
                $filters['final_date:lt'] = $today;
                // case future
            } elseif ($when === 'future') {
                $filters['initial_date:gt'] = $today;
                // sorry man, it was not meant to be
            } else {
                throw new BadRequestException("?when must be either null or a literal 'today', 'future, or 'past'.");
            }
        }

        $query = $query->handleTimeFilter($filters, 'initial_date');
        $query = $query->handleTimeFilter($filters, 'final_date');

        $userId = $filters['user_id'] ?? null;
        if ($userId) {
            $query->innerJoinWith(UsersTable::name(), function (Query $query) use ($userId) {
                return $query->where([
                    'Users.id' => $userId,
                ]);
            });
        }

        if ($when === 'future') {
            $query->orderAsc('initial_date');
        } else {
            $query->orderDesc('initial_date');
        }
        // Return query
        return $query->contain(OrganizersTable::name());
    }

    public function getEventWithRelations(string $id): Event
    {
        $query = $this->find()
            ->contain(FederationsTable::name())
            ->contain(OrganizersTable::name())
            ->contain(StagesTable::name() . '.' . StageTypesTable::name())
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

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->add('final_date', 'checkDates', [
                'rule' => function ($value, $context) {
                    if (
                        isset($context['data']['initial_date']) &&
                        strtotime($value) < strtotime($context['data']['initial_date'])
                    ) {
                        return false;
                    }
                    return true;
                },
                'message' => 'The final date cannot be earlier than the initial date.'
            ]);

        return $validator;
    }
}
