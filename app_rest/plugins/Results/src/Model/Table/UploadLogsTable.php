<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\I18n\FrozenTime;
use Cake\Database\Expression\IdentifierExpression;
use Cake\ORM\Query;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\Utility\Text;
use Results\Lib\UploadHelper;
use Results\Model\Entity\UploadLog;

class UploadLogsTable extends AppTable
{
    public const STATUS_OK = 200;

    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
    }

    public static function load(): self
    {
        /** @var ClassesTable $table */
        $table = parent::load();
        return $table;
    }

    public function getByEventId(string $eventId, string $stageId = null)
    {
        $q = $this->find()->where(['event_id' => $eventId]);
        if ($stageId) {
            $q->where(['stage_id' => $stageId]);
        }
        return $q->orderByDesc('created')->all();
    }

    public function keepOnlyNewestPerState(SelectQuery $query, string $eventId): SelectQuery
    {
        $newestPerState = $this->find()
            ->select([
                'newest_stage' => $this->aliasField('stage_id'),
                'newest_state' => $this->aliasField('state'),
                'newest_created' => $this->find()->func()->max('created'),
            ])
            ->where([$this->aliasField('event_id') => $eventId])
            ->groupBy([$this->aliasField('stage_id'), $this->aliasField('state')]);
        return $query
            ->innerJoin(['newest' => $newestPerState], [
                'newest.newest_stage' => new IdentifierExpression($this->aliasField('stage_id')),
                'newest.newest_state IS' => new IdentifierExpression($this->aliasField('state')),
                'newest.newest_created' => new IdentifierExpression($this->aliasField('created')),
            ])
            ->orderByAsc($this->aliasField('state'));
    }

    public function getLastLogsInStage(string $eventId, string $stageId): array
    {
        $logs = $this->keepOnlyNewestPerState($this->_inStage($eventId, $stageId), $eventId)->all();
        $byState = [];
        foreach ($logs as $log) {
            $byState[$log->state] = $log;
        }
        return array_values($byState);
    }

    private function _inStage(string $eventId, string $stageId): Query
    {
        return $this->find()->where(['event_id' => $eventId, 'stage_id' => $stageId]);
    }

    public function saveClearLog(string $eventId, string $stageId): UploadLog
    {
        /** @var UploadLog $log */
        $log = $this->newEmptyEntity();
        $log->id = Text::uuid();
        $log->event_id = $eventId;
        $log->stage_id = $stageId;
        $log->upload_type = null;
        $log->upload_status = UploadLogsTable::STATUS_OK;
        $log->state = $log->setClearState();

        /** @var UploadLog $saved */
        $saved = $this->saveOrFail($log);
        return $saved;
    }

    public function saveUploadLog(UploadHelper $helper): UploadLog
    {
        /** @var UploadLog $log */
        $log = $this->newEmptyEntity();
        $log->id = Text::uuid();
        $log->event_id = $helper->getEventId();
        $log->stage_id = $helper->getStageId();
        $log->upload_type = $helper->validateConfigChecker()->preCheckType();
        $log->upload_status = null;
        $log->state = $log->setUploadState();
        $log->info = '';

        /** @var UploadLog $saved */
        $saved = $this->saveOrFail($log);
        return $saved;
    }

    public function saveStateEnded(string $eventId, string $stageId): UploadLog
    {
        /** @var UploadLog $log */
        $log = $this->newEmptyEntity();
        $log->id = Text::uuid();
        $log->event_id = $eventId;
        $log->stage_id = $stageId;
        $log->upload_type = null;
        $log->upload_status = null;
        $log->state = $log->setEndedState();
        $log->info = '';

        /** @var UploadLog $saved */
        $saved = $this->saveOrFail($log);
        return $saved;
    }

    public function deleteStateEnded(string $eventId, string $stageId): int
    {
        $conditions = [
            'event_id' => $eventId,
            'stage_id' => $stageId,
            'state' => UploadLog::STATE_ENDED,
            'deleted is null'
        ];
        return $this->updateAll(['deleted' => new FrozenTime()], $conditions);
    }
}
