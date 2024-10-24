<?php

declare(strict_types = 1);

namespace App\Model\Table;

class OauthAccessTokensTable extends \RestApi\Model\Table\OauthAccessTokensTable
{
    public function getAuthorizationCode($code)
    {
        $code = $this->_findAuthorizationCodes($code);
        if (!$code) {
            return false;
        }
        $expires = $code->expires;
        if ($expires->isPast()) {
            //return false;// TODO
        }
        $code = $code->toArray();
        $code['expires'] = $expires->getTimestamp();
        return $code;
    }
    private function _findAuthorizationCodes($code)
    {
        return $this->OauthAuthorizationCodes->find()->where(['authorization_code' => $code])->firstOrFail();
    }
}
