<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Cake\Database\Schema\TableSchema;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;

class AppEntity extends Entity
{
    public function fastPatch(array $data, TableSchema $schema, string $timezone)
    {
        foreach ($this->_accessible as $field => $isAccessible) {
            $newFieldValue = $data[$field] ?? null;
            if ($isAccessible === true && $newFieldValue !== null) {
                $colType = $schema->getColumn($field)['type'] ?? '';
                if ($colType === TableSchemaInterface::TYPE_TIMESTAMP_FRACTIONAL) {
                    $newFieldValue = new FrozenTime($newFieldValue);// convert frozen time
                    $newFieldValue = $newFieldValue->setTimezone($timezone);
                }
                $this->_fields[$field] = $newFieldValue;
                $this->setDirty($field);
            }
        }
        return $this;
    }
}
