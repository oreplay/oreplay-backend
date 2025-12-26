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

    public function setSplitsAsSimpleArray(array $courseStations)
    {
        if ($this->splits && is_array($this->splits)) {
            usort($this->splits, function ($a, $b) use ($courseStations) {
                if (in_array($a->station, $courseStations) && in_array($b->station, $courseStations)) {
                    $posA = array_search($a->station, $courseStations);
                    $posB = array_search($b->station, $courseStations);
                    return $posA <=> $posB;
                }
                if ($a->reading_time === null && $b->reading_time === null) {
                    return 0;
                }
                if ($a->reading_time === null) {
                    return 1; // null goes after non-null
                }
                if ($b->reading_time === null) {
                    return -1; // non-null goes before null
                }
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
