<?php

declare(strict_types = 1);

namespace Rankings\Controller;

use App\Lib\FullBaseUrl;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Exception\NotFoundException;
use Rankings\Model\Table\RankingsTable;
use React\Http\Browser;
use React\Promise\PromiseInterface;
use RestApi\Lib\Helpers\CookieHelper;
use Results\Controller\ApiController;
use Results\Model\Table\RunnersTable;

class RankingComputeClassController extends ApiController
{
    private const string SECRET_PARAM = 'secret';
    private RunnersTable $Runners;
    private RankingsTable $Rankings;

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

        usort($participants, RunnersTable::sortResults());
        $saved = $this->Rankings->saveRanking($rankingId, $stageId, $classId, $participants);
        if ($saved) {
            $this->return = $saved;
        } else {
            $err = 'Class without position one runner ' . $classId . ' ' . json_encode($participants);
            throw new InternalErrorException($err);
        }
    }

    private function _validateSecret(array $data): void
    {
        $querySecret = $data[self::SECRET_PARAM] ?? null;
        unset($data[self::SECRET_PARAM]);
        if ($querySecret !== $this->getSecret()) {
            throw new ForbiddenException('Invalid secret in query: ' . $querySecret);
        }
    }
}
