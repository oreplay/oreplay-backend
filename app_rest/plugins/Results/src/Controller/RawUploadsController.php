<?php

declare(strict_types = 1);

namespace Results\Controller;

use Cake\Http\Exception\ForbiddenException;
use Results\Lib\UploadHelper;
use Results\Model\Table\RawUploadsTable;
use Results\Model\Table\TokensTable;
use Results\Model\Table\UploadLogsTable;

class RawUploadsController extends ApiController
{
    public function isPublicController(): bool
    {
        return true;
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
}
