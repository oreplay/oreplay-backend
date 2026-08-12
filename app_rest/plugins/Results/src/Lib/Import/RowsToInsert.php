<?php

declare(strict_types = 1);

namespace Results\Lib\Import;

use App\Model\Table\AppTable;
use Cake\Datasource\EntityInterface;
use Cake\I18n\FrozenTime;
use Results\Model\Entity\Control;
use Results\Model\Entity\Split;

class RowsToInsert
{
    private const ROWS_PER_STATEMENT = 500;

    private FrozenTime $_now;

    public function __construct()
    {
        $this->_now = new FrozenTime();
    }

    private array $_controls = [];
    private array $_splits = [];

    public function addControl(Control $control): void
    {
        $this->_controls[$control->id] = $control;
    }

    public function addSplit(Split $split): void
    {
        $this->_splits[] = $split;
    }

    public function insertAndForget(AppTable $controls, AppTable $splits): int
    {
        $written = $this->_insertInto($controls, array_values($this->_controls));
        $written += $this->_insertInto($splits, $this->_splits);
        $this->_controls = [];
        $this->_splits = [];
        return $written;
    }

    private function _timestamped(EntityInterface $entity, array $columns): array
    {
        $row = $entity->extract($columns);
        foreach (['created', 'modified'] as $stamp) {
            if (array_key_exists($stamp, $row) && !$row[$stamp]) {
                $row[$stamp] = $this->_now;
            }
        }
        return $row;
    }

    /**
     * @param EntityInterface[] $entities
     */
    private function _insertInto(AppTable $table, array $entities): int
    {
        if (!$entities) {
            return 0;
        }
        $schema = $table->getSchema();
        $columns = $schema->columns();
        $types = [];
        foreach ($columns as $column) {
            $types[$column] = $schema->getColumnType($column);
        }
        $written = 0;
        foreach (array_chunk($entities, self::ROWS_PER_STATEMENT) as $chunk) {
            $query = $table->insertQuery()->insert($columns, $types);
            foreach ($chunk as $entity) {
                $query->values($this->_timestamped($entity, $columns));
            }
            $written += $query->execute()->rowCount();
        }
        return $written;
    }
}
