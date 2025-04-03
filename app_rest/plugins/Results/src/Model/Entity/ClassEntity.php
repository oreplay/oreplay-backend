<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Results\Lib\UploadHelper;

/**
 * @property mixed $oe_key
 * @property string $short_name
 * @property string $long_name
 * @property string $event_id
 * @property string $stage_id
 * @property Runner[] $runners
 * @property Split[] $splits
 * @property Team[] $teams
 * @property Course $course
 */
class ClassEntity extends AppEntity
{
    public const ME = 'd8a87faf-68a4-487b-8f28-6e0ead6c1a57';
    public const FE = 'd8a87faf-68a4-487b-8f28-6e0ead6c1a56';

    protected $_accessible = [
        '*' => false,
        'id' => false,
        'short_name' => true,
        'long_name' => true,
        'oe_key' => true,
    ];

    protected $_virtual = [
    ];

    protected $_hidden = [
        'event_id',
        'stage_id',
        'course_id',
        'uuid',
        'oe_key',
        'upload_hash',
        'created',
        'modified',
        'deleted',
    ];

    public function isSameUploadHash(array $compareArray): bool
    {
        $uploadHash = UploadHelper::md5Encode($compareArray);
        $existingHash = $this->_fields['upload_hash'] ?? 'hash_class_does_not_exist';
        return $existingHash == $uploadHash;
    }

    public function setHash(array $resultData)
    {
        $hash = UploadHelper::md5Encode($resultData);
        $this->_fields['upload_hash'] = $hash;
        $this->setDirty('upload_hash');
    }

    public function setSplitsAsSimpleArray()
    {
        if ($this->splits && is_array($this->splits)) {
            usort($this->splits, function ($a, $b) {
                return $a->reading_time <=> $b->reading_time; // Ascending order
            });
            /** @var Split $s */
            foreach ($this->splits as $s) {
                $s->setStationVisible();
                $s->setHidden(['reading_time'], true);
            }
        }
    }
}
