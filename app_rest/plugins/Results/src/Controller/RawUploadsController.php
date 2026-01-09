<?php

declare(strict_types = 1);

namespace Results\Controller;

use App\Lib\FullBaseUrl;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\I18n\FrozenTime;
use RestApi\Lib\Exception\DetailedException;
use Results\Lib\UploadHelper;
use Results\Model\Entity\UploadLog;
use Results\Model\Table\RawUploadsTable;
use Results\Model\Table\TokensTable;
use Results\Model\Table\UploadLogsTable;

class RawUploadsController extends ApiController
{
    public function isPublicController(): bool
    {
        return true;
    }

    protected function getList()
    {
        $eventId = $this->_checkSecret();
        $stageId = $this->request->getQuery('stage_id');
        $res = UploadLogsTable::load()->getByEventId($eventId, $stageId);
        $secret = $this->request->getParam('eventID');
        $toRet = [];
        /** @var UploadLog $record */
        foreach ($res as $record) {
            $url = FullBaseUrl::host().'/api/v1/events/'.$secret.'/rawUploads/';
            $elem = $record->toSimpleArray($url);
            if (!$stageId) {
                $elem['link_stage'] = $url . '?stage_id='.$record->stage_id;
            }
            $toRet[] = $elem;
        }
        $this->return = $toRet;
    }

    private function _checkSecret(): string
    {
        $secret = $this->request->getParam('eventID');
        $eventId = substr($secret, 0, 36);
        $token = substr($secret, 36);
        $isDesktopClientAuthenticated = TokensTable::load()->isValidEventToken($eventId, $token);
        if (!$isDesktopClientAuthenticated) {
            throw new DetailedException('Invalid event secret ' . $secret);
        }
        return $eventId;
    }

    protected function getData($id)
    {
        $eventId = $this->_checkSecret();

        $created = new FrozenTime($id);
        if (!$created) {
            throw new BadRequestException('Invalid cretaed time');
        }
        $raw = RawUploadsTable::load()->getFirstCreated($created, $eventId);
        $content = json_decode($raw->file_data, true);
        $this->_writeLastUploadJson($content, TMP . 'rawFileData.json');
        $raw->file_data = $content;
        $this->return = $raw;
    }

    private function _writeLastUploadJson(array $content, string $path)
    {
        $file = new \SplFileObject($path, 'w+');
        $file->fwrite(json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    protected function addNew($data)
    {
        $helper = new UploadHelper($data, $this->request->getParam('eventID'));

        $token = $this->_getBearer();
        $isDesktopClientAuthenticated = TokensTable::load()->isValidEventToken($helper->getEventId(), $token);
        if (!$isDesktopClientAuthenticated) {
            throw new ForbiddenException('Invalid Bearer token');
        }

        $helper->validateConfigChecker();

        $log = UploadLogsTable::load()->saveUploadLog($helper);
        RawUploadsTable::load()->saveFile($log, $helper);
        $this->return = $log;
    }

    private function _getBearer(): ?string
    {
        $auth = $this->getRequest()->getHeader('Authorization')[0] ?? null;
        if (!$auth) {
            return null;
        }
        return substr($auth, strlen('Bearer '));
    }

    protected function delete($id)
    {
        if ($id !== 'old') {
            throw new BadRequestException('Only "old" deletion is allowed');
        }
        RawUploadsTable::load()->hardDeleteOld();
        $this->return = false;
    }
}
