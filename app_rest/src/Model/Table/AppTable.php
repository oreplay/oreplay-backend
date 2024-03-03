<?php

declare(strict_types = 1);

namespace App\Model\Table;

use Cake\Utility\Text;
use RestApi\Model\Table\RestApiTable;

abstract class AppTable extends RestApiTable
{
    const TABLE_PREFIX = '';


    public function patchFromNewWithUuid(array $data)
    {
        $entity = $this->newEmptyEntity();
        $entity->id = Text::uuid();
        return $this->patchEntity($entity, $data);
    }
}
