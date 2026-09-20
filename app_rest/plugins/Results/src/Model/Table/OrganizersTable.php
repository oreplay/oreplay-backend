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
    private const COLUMN_LENGTH = 50;

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
            ->maxLength('name', self::COLUMN_LENGTH)
            ->allowEmptyString('country')
            ->maxLength('country', self::COLUMN_LENGTH)
            ->allowEmptyString('region')
            ->maxLength('region', self::COLUMN_LENGTH);
    }

    public function getOrganizers()
    {
        return $this->find()
            ->orderByAsc('name')->all();
    }
}
