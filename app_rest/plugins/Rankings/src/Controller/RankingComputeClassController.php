<?php

declare(strict_types = 1);

namespace Rankings\Controller;

use App\Lib\FullBaseUrl;
use App\Model\Table\UsersTable;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Rankings\Model\Table\RankingsTable;
use React\Http\Browser;
use React\Promise\PromiseInterface;
use RestApi\Lib\Exception\DetailedException;
use RestApi\Lib\Helpers\CookieHelper;
use Results\Controller\ApiController;
use Results\Lib\ResultsSorter;
use Results\Model\Table\RunnersTable;

class RankingComputeClassController extends ApiController
{
    private const string SECRET_PARAM = 'secret';
    private RunnersTable $Runners;
    private RankingsTable $Rankings;
    private mixed $_currentUid = null;

    public function initialize(): void
    {
        parent::initialize();
        $this->Runners = RunnersTable::load();
        $this->Rankings = RankingsTable::load();
    }

    public function isPublicController(): bool
    {
        return true;
    }

    public static function getSecret()
    {
        $secret = 'rankingSecret';
        $helper = new CookieHelper();
        $encrypted = $helper->writeWithName($secret, $secret, 60);
        return $encrypted->getValue();
    }

    public static function rankingRequest(Browser $browser, string $subpath): PromiseInterface
    {
        $domain = FullBaseUrl::host();
        if ($domain === 'http://dev.example.com') {
            $domain = 'https://www.oreplay.es';
            //$domain = 'http://host.docker.internal';
        } else if ($domain === 'https://localhost') {
            $domain = 'http://localhost';
        }
        $url = $domain . '/api/v1/rankings' . $subpath;

        $payload = [self::SECRET_PARAM => self::getSecret()];

        return $browser->post($url, ['Content-Type' => 'application/json'], json_encode($payload));
    }

    protected function addNew($data)
    {
        $rankingId = $this->request->getParam('rankingID');
        $eventId = $this->request->getParam('eventID');
        $stageId = $this->request->getParam('stageID');
        $classId = $this->request->getParam('classID');

        $this->_validateSecret($data);
        $participants = $this->Runners->findRunnersInStage($eventId, $stageId, ['class_id' => $classId])->toArray();

        if (!$participants) {
            throw new NotFoundException('Not found participants');
        }

        usort($participants, ResultsSorter::sortStages());
        try {
            $this->return = $this->Rankings->saveRanking($rankingId, $stageId, $classId, $participants);
        } catch (DetailedException $e) {
            if ($this->_currentUid) {
                // request from frontend throw exception
                throw $e;
            } else {
                // from parallel php use soft error
                $this->log($e->getMessage());
                $this->return = ['error' => $e->getMessage()];
            }
        }
    }

    private function _validateSecret(array $data): void
    {
        $querySecret = $data[self::SECRET_PARAM] ?? null;
        unset($data[self::SECRET_PARAM]);
        if ($querySecret === 'auth') {
            // request from frontend
            $a = $this->getLocalOauth()->verifyAuthorizationAndGetToken();
            $this->_currentUid = $a->getUserID();
            UsersTable::load()->getManagerOrFail($this->_currentUid);
        } else {
            // from pararell php
            if ($querySecret !== $this->getSecret()) {
                throw new ForbiddenException('Invalid secret in query: ' . $querySecret);
            }
        }
    }
}
