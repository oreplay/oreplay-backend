<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Cake\Database\Schema\TableSchema;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;

class AppEntity extends Entity
{
    public function fastPatch(array $data, TableSchema $schema)
    {
        foreach ($this->_accessible as $field => $isAccessible) {
            $newFieldValue = $data[$field] ?? null;
            if ($isAccessible === true && $newFieldValue !== null) {
                $colType = $schema->getColumn($field)['type'] ?? '';
                if ($colType === TableSchemaInterface::TYPE_TIMESTAMP_FRACTIONAL) {
                    $newFieldValue = new FrozenTime($newFieldValue);// convert frozen time
                    $this->setDirty($field);
                }
                $this->_fields[$field] = $newFieldValue;
            }
        }
        return $this;
    }
}