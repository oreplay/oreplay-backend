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
    use UploadHashTrait;

    public const ME = 'd8a87faf-68a4-487b-8f28-6e0ead6c1a57';
    public const FE = 'd8a87faf-68a4-487b-8f28-6e0ead6c1a56';

    protected array $_accessible = [
        '*' => false,
        'id' => false,
        'short_name' => true,
        'long_name' => true,
        'oe_key' => true,
    ];

    protected array $_virtual = [
    ];

    protected array $_hidden = [
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

    public function addRunners(array $runners)
    {
        $this->runners = $runners;
    }

    public function isShortNameIn(array $classNames): bool
    {
        return in_array($this->short_name, $classNames, true);
    }
}
