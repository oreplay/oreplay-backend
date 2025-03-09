<?php

declare(strict_types = 1);

namespace RadioRelay\Lib\Cpi;

use Cake\Http\Exception\BadRequestException;
use Cake\I18n\FrozenTime;
use Results\Lib\StrGenerator;
use Results\Lib\UploadInterface;
use Results\Lib\UploadControlsTrait;

class PayloadParser implements UploadInterface
{
    use UploadControlsTrait;

    private array $data;

    private const SECRET_LEN = StrGenerator::LENGTH;
    private const UUID_LEN = 36;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getEventId(): string
    {
        $secret = $this->data['data'][1] ?? null;
        if (!$secret) {
            throw new BadRequestException('Use the secret and event token as password');
        }
        if (strpos($secret, '-') === 8) {
            $eventId = substr($secret, 0, self::UUID_LEN);
            if (!$this->_isUuid($eventId)) {
                throw new BadRequestException('The Password value does not start with an event ID');
            }
        } else {
            $eventId = substr($secret, self::UUID_LEN * -1);
            if (!$this->_isUuid($eventId)) {
                throw new BadRequestException('The Password value does not end with an event ID');
            }
        }
        return $eventId;
    }

    public function getSecret()
    {
        $secret = $this->data['data'][1] ?? null;
        if (!$secret) {
            throw new BadRequestException('Use the secret token as password');
        }
        if (strpos($secret, '-') === 8) {
            $ret = substr($secret, self::SECRET_LEN * -1);
            if (!$this->_isSecret($ret)) {
                throw new BadRequestException('The Password value does not end with a secret');
            }
        } else {
            $ret = substr($secret, 0, self::SECRET_LEN);
            if (!$this->_isSecret($ret)) {
                throw new BadRequestException('The Password value does not start with a secret');
            }
        }
        return $ret;
    }

    public function getStageId(): string
    {
        $stageId = $this->data['data'][0] ?? null;
        if (!$stageId) {
            throw new BadRequestException('Use stage ID as User');
        }
        if (!$this->_isUuid($stageId)) {
            throw new BadRequestException('The User value does not look like a stage ID');
        }
        return $stageId;
    }

    private function _isUuid(string $uuid)
    {
        return strlen($uuid) === self::UUID_LEN && substr_count($uuid, "-") === 4;
    }

    private function _isSecret(string $secret)
    {
        return strlen($secret) === self::SECRET_LEN && substr_count($secret, "-") === 0;
    }

    public function getPunches()
    {
        if (!isset($this->data['punches'][0])) {
            throw new BadRequestException('Punches are mandatory');
        }
        return $this->data['punches'];
    }

    public static function getReadingTime(array $punch): FrozenTime
    {
        $time = $punch['date'] . ' ' . $punch['time'];
        return new FrozenTime($time);
    }
}
