<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property string $short_name
 * @property string $event_id
 * @property string $stage_id
 * @property Runner[] $runners
 * @property Course $course
 */
class ClassEntity extends Entity
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
        $uploadHash = md5(json_encode($compareArray));
        $existingHash = $this->_fields['upload_hash'] ?? 'hash_does_not_exist';
        return $existingHash == $uploadHash;
    }
}
