<?php

declare(strict_types = 1);

namespace Results\Lib\Import;

use Cake\Datasource\EntityInterface;
use Cake\Event\EventManager;

class SavedRowsCheck
{
    private const WRITTEN_OUTSIDE_THE_ORM = 'splits';

    private array $_visitedObjectIds = [];
    private int $_newRows = 0;

    public function rowsMissingAfter(EntityInterface $graph, callable $save): int
    {
        $expected = $this->_newRowsIn($graph);
        $saved = 0;
        $countSaves = function () use (&$saved): void {
            $saved++;
        };
        EventManager::instance()->on('Model.afterSave', $countSaves);
        try {
            $save();
        } finally {
            EventManager::instance()->off('Model.afterSave', $countSaves);
        }
        return max(0, $expected - $saved);
    }

    private function _newRowsIn(EntityInterface $graph): int
    {
        $this->_visitedObjectIds = [];
        $this->_newRows = 0;
        $this->_walk($graph);
        return $this->_newRows;
    }

    private function _walk(mixed $value): void
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->_walk($item);
            }
            return;
        }
        if (!($value instanceof EntityInterface)) {
            return;
        }
        $objectId = spl_object_id($value);
        if (isset($this->_visitedObjectIds[$objectId])) {
            return;
        }
        $this->_visitedObjectIds[$objectId] = true;
        if ($value->isNew()) {
            $this->_newRows++;
        }
        foreach ($this->_fieldsHoldingEntities($value) as $field) {
            $this->_walk($value->get($field));
        }
    }

    private function _fieldsHoldingEntities(EntityInterface $entity): array
    {
        $fields = array_diff(
            array_merge($entity->getVisible(), $entity->getHidden()),
            $entity->getVirtual(),
            [self::WRITTEN_OUTSIDE_THE_ORM]
        );
        return array_filter($fields, fn($field) => is_object($entity->get($field)) || is_array($entity->get($field)));
    }
}
