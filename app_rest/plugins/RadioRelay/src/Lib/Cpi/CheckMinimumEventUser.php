<?php

declare(strict_types = 1);

namespace RadioRelay\Lib\Cpi;

use Cake\Http\Exception\BadRequestException;
use Cake\ORM\Query;
use Results\Model\Entity\Event;
use Results\Model\Table\EventsTable;
use Results\Model\Table\StagesTable;
use Results\Model\Table\TokensTable;

class CheckMinimumEventUser
{
    private PayloadParser $data;
    private EventsTable $Events;
    private TokensTable $Tokens;

    public function __construct(PayloadParser $data)
    {
        $this->data = $data;
    }

    public function setEventsTable(EventsTable $table): CheckMinimumEventUser
    {
        $this->Events = $table;
        return $this;
    }

    public function setTokensTable(TokensTable $table): CheckMinimumEventUser
    {
        $this->Tokens = $table;
        return $this;
    }

    public function process(): array
    {
        $cred = $this->data;
        $res = $this->Tokens->isValidEventToken($cred->getEventId(), $cred->getSecret());
        if ($res) {
            /** @var Event $e */
            $e = $this->Events->find()
                ->where(['id' => $cred->getEventId()])
                ->contain(StagesTable::name(), function (Query $q) use ($cred) {
                    return $q->where(['id' => $cred->getStageId()]);
                })
                ->firstOrFail();
            if (!$e) {
                throw new BadRequestException('Event id (password) not found ' . $cred->getEventId());
            }
            if (!$e->stages) {
                throw new BadRequestException('Stage id (username) does not belong to event ' . $e->id);
            }
            $eventTitle = $e->description . ' (' . $e->stages[0]->description . ')';
        } else {
            $eventTitle = 'Invalid password (event ID) ' . $cred->getEventId();
        }
        $firstParamData = $cred->getStageId();
        $baseTime = '00:00:00'; // e.g '10:30:00'
        $eventType = '0'; // 0=Classic; 1=Mass Start; 2=Chase Start; 3=Relays; 4=Rogaine
        $controlStation = ''; // Usually empty. Control station associated to the device
        $eventOrigin = '0';
        return [
            $firstParamData,
            $eventTitle,
            $baseTime,
            $eventType,
            $controlStation,
            $eventOrigin,
            $cred->getEventId().$cred->getSecret(),
            ''
        ];
    }
}
