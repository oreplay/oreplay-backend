<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Lib\Exception\InvalidPayloadException;
use App\Model\Table\AppTable;
use Cake\I18n\FrozenTime;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\Utility\Text;
use Results\Lib\UploadHelper;
use Results\Model\Entity\RawUpload;
use Results\Model\Entity\UploadLog;
use Results\Lib\UploadConfigChecker;

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
        $raw->file_data = $this->_storableBody($helper);
        $raw->upload_log_id = $log->id;

        /** @var RawUpload $saved */
        $saved = $this->saveOrFail($raw);
        return $saved;
    }

    /**
     * `file_data` is a utf8 column, and a SportSoftware XML export is often windows-1252 — MySQL rejects
     * those bytes outright ("Incorrect string value: '\xED'"). Such a body is stored base64 so nothing is
     * lost or silently mangled; a reader tells them apart by the leading '<'.
     *
     * A stopgap: docs/upload-xml-input.md 12 still has to decide what raw_uploads should hold for XML,
     * and gzip is worth weighing there given how large this table already is.
     */
    private function _storableBody(UploadHelper $helper): string
    {
        $body = $helper->getRawBody();
        if ($body === null) {
            return (string)json_encode($helper->getData());
        }
        return mb_check_encoding($body, 'UTF-8') ? $body : base64_encode($body);
    }

    public function hardDeleteOld(int $limit = 900): int
    {
        $cutoff = new FrozenTime('-12 days');

        $query = $this->find()
            ->select(['id'])
            ->where(['created <' => $cutoff])
            ->orderBy(['created' => 'ASC'])
            ->limit($limit)
            ->enableHydration(false);

        $ids = [];
        foreach ($query as $row) {
            $ids[] = $row['id'];
        }

        if (empty($ids)) {
            return 0;
        }

        return $this->deleteAll(['id IN' => $ids]);
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
        if (!is_array($toRet)) {
            // an XML upload is stored as the original bytes, which have no ids to overwrite here
            throw new InvalidPayloadException('Re-uploading a stored XML upload is not supported yet');
        }
        $envelope = UploadConfigChecker::ENVELOPE_KEY;
        $toRet[$envelope]['event']['id'] = $eventId;
        $stages = $toRet[$envelope]['event']['stages'] ?? [];
        foreach ($stages as $i => $stage) {
            $toRet[$envelope]['event']['stages'][$i]['id'] = $data['stage_id'];
        }
        return $toRet;
    }
}
