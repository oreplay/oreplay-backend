<?php

declare(strict_types = 1);

namespace RadioRelay\Controller;

use App\Controller\ApiController;
use Cake\Http\Exception\BadRequestException;
use RadioRelay\Lib\Cpi\PayloadParser;
use RadioRelay\Lib\Cpi\ProcessPunches;
use Results\Model\Entity\Event;
use Results\Model\Table\EventsTable;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\TokensTable;

class CpiServerController extends ApiController
{
    public function isPublicController(): bool
    {
        return true;
    }

    protected function addNew($data)
    {
        $this->log('CpiServerController: [21] ' . json_encode($data));
        switch ($data['order']) {
            case 'ProcessPunches':
                try {
                    $processor = new ProcessPunches(new PayloadParser($data));
                    $toRet = $processor->setRunnersTable(RunnersTable::load())->process();
                } catch (BadRequestException $e) {
                    $this->log('CpiServerController: [20] ' . $e->getMessage()
                        . ' ------------- ' . $e->getTraceAsString());
                    $toRet = ['KO - Error: ' . $e->getMessage(), '0', '0'];
                }
                break;
            case 'CheckConnectivity':
            case 'ProcessBatteryStatus':
                $toRet = ['OK'];
                break;
            case 'CheckMinimumEventUser':
                try {
                    $cred = new PayloadParser($data);
                    $res = TokensTable::load()->isValidEventToken($cred->getEventId(), $cred->getSecret());
                    if ($res) {
                        /** @var Event $event */
                        $event = EventsTable::load()->find()->where(['id' => $cred->getEventId()])->firstOrFail();
                        $eventTitle = $event->description;
                    } else {
                        $eventTitle = 'Invalid event ID ' . $cred->getEventId();
                    }
                    $baseTime = '00:00:00'; // e.g '10:30:00'
                    $eventType = '0'; // 0=Classic; 1=Mass Start; 2=Chase Start; 3=Relays; 4=Rogaine
                    $controlStation = ''; // Usually empty. Control station associated to the device
                    $eventOrigin = '0';
                    $toRet = [
                        $cred->getEventId(),
                        $eventTitle,
                        $baseTime,
                        $eventType,
                        $controlStation,
                        $eventOrigin,
                        $cred->getSecret(),
                        ''
                    ];
                } catch (BadRequestException $e) {
                    $toRet = ['-1', $e->getMessage(), '', '', '', '', '', ''];
                }
                break;
            default:
                throw new BadRequestException('Invalid payload ' . json_encode($data));
        }
        $this->return = $toRet;
    }
}
