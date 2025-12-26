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

    public function getFirstCreated(FrozenTime $created, string $eventId): RawUpload
    {
        /** @var RawUpload $res */
        $res = $this->find()
            ->where(['created >=' => $created, 'event_id' => $eventId])
            ->orderByAsc('created')
            ->limit(1)
            ->firstOrFail();
        return $res;
    }

    public function getByUploadLogId(string $uploadLogId): RawUpload
    {
        /** @var RawUpload $res */
        $res = $this->find()
            ->where(['upload_log_id' => $uploadLogId])
            ->firstOrFail();
        return $res;
    }

    public function getReUploadedData(array $data, string $eventId): ?array
    {
        $arrayKeys = array_keys($data);
        sort($arrayKeys);
        if ($arrayKeys !== ['raw_upload_id', 'stage_id']) {
            return null;
        }
        $res = $this->find()
            ->where(['id' => $data['raw_upload_id']])
            ->limit(1)
            ->firstOrFail();

        $toRet = json_decode($res->file_data, true);
        $toRet['oreplay_data_transfer']['event']['id'] = $eventId;
        $stages = $toRet['oreplay_data_transfer']['event']['stages'] ?? [];
        foreach ($stages as $i => $stage) {
            $toRet['oreplay_data_transfer']['event']['stages'][$i]['id'] = $data['stage_id'];
        }
        return $toRet;
    }
}
