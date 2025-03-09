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
                    $toRet = ['KO', '0', $e->getMessage()];
                }
                break;
            case 'CheckConnectivity':
            case 'ProcessBatteryStatus':
                $toRet = ['OK'];
                break;
            case 'CheckMinimumEventUser':
                $cred = new PayloadParser($data['data']);
                $res = TokensTable::load()->isValidEventToken($cred->getEventId(), $cred->getSecret());
                if ($res) {
                    /** @var Event $event */
                    $event = EventsTable::load()->find()->where(['id' => $cred->getEventId()])->firstOrFail();
                    $eventTitle = $event->description;
                } else {
                    $eventTitle = 'Invalid event ID ' . $cred->getEventId();
                }
                $startsAt = ''; // e.g '10:30:00'
                $toRet = [$cred->getEventId(), $eventTitle, $startsAt, '', '', '', $cred->getSecret()];
                break;
            default:
                throw new BadRequestException('Invalid payload ' . json_encode($data));
        }
        $this->return = $toRet;
    }
}
