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
 * @property Course $course
 */
class ClassEntity extends AppEntity
{
    public const ME = 'd8a87faf-68a4-487b-8f28-6e0ead6c1a57';

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
        'long_name',
        'upload_hash',
        'created',
        'modified',
        'deleted',
    ];

    public function isSameUploadHash(array $compareArray): bool
    {
        $uploadHash = UploadHelper::md5Encode($compareArray);
        $existingHash = $this->_fields['upload_hash'] ?? 'hash_does_not_exist';
        return $existingHash == $uploadHash;
    }

    public function setHash(array $resultData)
    {
        $hash = UploadHelper::md5Encode($resultData);
        //$this->_fields['upload_hash'] = $hash;
        $this->setDirty('upload_hash');
    }
}
