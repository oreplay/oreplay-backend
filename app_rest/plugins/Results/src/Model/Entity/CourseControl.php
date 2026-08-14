<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

/**
 * @property mixed $station
 * @property mixed $order_number
 * @property mixed $is_radio
 * @property string $course_id
 * @property string $control_id
 */
class CourseControl extends AppEntity
{
    protected array $_accessible = [
        '*' => false,
        'id' => false,
        'station' => true,
        'order_number' => true,
    ];

    protected array $_virtual = [
    ];

    protected array $_hidden = [
        'event_id',
        'stage_id',
        'course_id',
        'control_id',
        'kilometer',
        'description',
        'created',
        'modified',
        'deleted',
    ];
}
