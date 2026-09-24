<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Lib\Consts\CacheGrp;
use App\Model\Table\AppTable;
use Cake\Cache\Cache;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\Validation\Validator;
use Results\Lib\ClubMatcher\OrganizerMatcher;

/**
 * @property EventsTable $Events
 */
class OrganizersTable extends AppTable
{
    private const MATCHER_CACHE_KEY = '_organizerMatcher';

    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        EventsTable::addBelongsTo($this);
    }

    public static function load(): self
    {
        /** @var OrganizersTable $table */
        $table = parent::load();
        return $table;
    }

    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->requirePresence('name', 'create')
            ->notEmptyString('name')
            ->maxLength('name', 50)
            ->allowEmptyString('country_code')
            ->maxLength('country_code', 2)
            ->allowEmptyString('region_code')
            ->maxLength('region_code', 3);
    }

    public function getOrganizers()
    {
        return $this->find()
            ->orderByAsc('name')->all();
    }

    public function getCachedMatcher(): OrganizerMatcher
    {
        return Cache::remember(self::MATCHER_CACHE_KEY, function () {
            return new OrganizerMatcher($this->getOrganizers()->toList());
        }, CacheGrp::EXTRALONG);
    }

    public function deleteMatcherCache(): void
    {
        Cache::delete(self::MATCHER_CACHE_KEY, CacheGrp::EXTRALONG);
    }
}
