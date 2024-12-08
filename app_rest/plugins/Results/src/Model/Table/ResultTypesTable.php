<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Lib\Exception\InvalidPayloadException;
use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;
use Results\Lib\UploadConfigChecker;
use Results\Model\Entity\ResultType;

/**
 * @property RunnerResultsTable $RunnerResults
 */
class ResultTypesTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        RunnerResultsTable::addBelongsTo($this);
    }

    public static function load(): self
    {
        /** @var ResultTypesTable $table */
        $table = parent::load();
        return $table;
    }

    private function getCached(string $id): ResultType
    {
        /** @var ResultType $res */
        $res = $this->find()->where(['id' => $id])->cache('getCached_' . $id)->firstOrFail();
        return $res;
    }

    public function getCachedWithDefault(UploadConfigChecker $checker, $typeId): ResultType
    {
        if ($checker->isStartLists()) {
            $typeId = ResultType::STAGE;
        }
        if (!$typeId) {
            throw new InvalidPayloadException('runner_results.result_type.id is mandatory');
        }
        return $this->getCached($typeId);

    }
}
