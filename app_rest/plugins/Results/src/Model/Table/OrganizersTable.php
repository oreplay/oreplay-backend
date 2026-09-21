<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\Validation\Validator;

/**
 * @property EventsTable $Events
 */
class OrganizersTable extends AppTable
{
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
}
