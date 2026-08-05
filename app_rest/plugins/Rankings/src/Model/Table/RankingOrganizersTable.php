<?php

declare(strict_types = 1);

namespace Rankings\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\Validation\Validator;

class RankingOrganizersTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('first_name', 'create')
            ->notEmptyString('first_name')
            ->maxLength('first_name', 255)
            ->requirePresence('last_name', 'create')
            ->notEmptyString('last_name')
            ->maxLength('last_name', 255)
            ->allowEmptyString('description')
            ->maxLength('description', 255)
            ->requirePresence('stage_order_id', 'create')
            ->notEmptyString('stage_order_id')
            ->maxLength('stage_order_id', 36)
            ->allowEmptyString('runner_id')
            ->maxLength('runner_id', 36);
        return $validator;
    }

    public static function load(): self
    {
        /** @var RankingOrganizersTable $table */
        $table = parent::load();
        return $table;
    }
}
