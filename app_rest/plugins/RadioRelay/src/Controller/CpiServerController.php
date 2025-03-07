<?php

declare(strict_types = 1);

namespace RadioRelay\Controller;

use App\Controller\ApiController;
use Cake\Http\Exception\BadRequestException;
use Results\Model\Entity\Event;
use Results\Model\Table\EventsTable;
use Results\Model\Table\TokensTable;

class CpiServerController extends ApiController
{
    public function isPublicController(): bool
    {
        return true;
    }

    protected function addNew($data)
    {
        switch ($data['order']) {
            case 'CheckConnectivity':
                $toRet = ['OK'];
                break;
            case 'CheckMinimumEventUser':
                list($eventId, $password) = $data['data'];
                $res = TokensTable::load()->isValidEventToken($eventId, $password);
                if ($res) {
                    /** @var Event $event */
                    $event = EventsTable::load()->find()->where(['id' => $eventId])->firstOrFail();
                    $eventTitle = $event->description;
                } else {
                    $eventTitle = 'Invalid event ID ' . $eventId;
                }
                $startsAt = ''; // e.g '10:30:00'
                $toRet = [$eventId, $eventTitle, $startsAt, '', '', '', $password];
                break;
            default:
                throw new BadRequestException('Invalid payload ' . json_encode($data));
        }
        $this->return = $toRet;
    }
}
