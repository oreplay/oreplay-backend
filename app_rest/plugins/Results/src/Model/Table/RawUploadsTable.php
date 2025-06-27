<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\I18n\FrozenTime;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\Utility\Text;
use Results\Lib\UploadHelper;
use Results\Model\Entity\RawUpload;
use Results\Model\Entity\UploadLog;

class RawUploadsTable extends AppTable
{
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

    public function saveFile(UploadLog $log, UploadHelper $helper): RawUpload
    {
        /** @var RawUpload $raw */
        $raw = $this->newEmptyEntity();
        $raw->id = Text::uuid();
        $raw->event_id = $helper->getEventId();
        $raw->stage_id = $helper->getStageId();
        $raw->file_data = json_encode($helper->getData(), JSON_PRETTY_PRINT);
        $raw->upload_log_id = $log->id;

        /** @var RawUpload $saved */
        $saved = $this->saveOrFail($raw);
        return $saved;
    }

    public function hardDeleteOld(): int
    {
        return $this->deleteAll(['created <' => new FrozenTime('-12days')]);
    }

    public function getFirstCreated(FrozenTime $created, string $eventId)
    {
        return $this->find()
            ->where(['created >' => $created, 'event_id' => $eventId])
            ->orderAsc('created')
            ->limit(1)
            ->firstOrFail();
    }
}
