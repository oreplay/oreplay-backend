<?php

declare(strict_types = 1);

namespace Rankings\Controller;

use App\Model\Table\UsersTable;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Loop;
use React\Http\Browser;
use React\Http\Message\Response;
use React\Http\Message\ResponseException;
use React\Socket\Connector;
use Rankings\Model\Table\RankingsTable;
use Results\Controller\ApiController;
use Results\Model\Entity\ClassEntity;
use Results\Model\Table\StageOrdersTable;

class RankingComputeStageController extends ApiController
{
    private RankingsTable $Rankings;
    private UsersTable $Users;
    private StageOrdersTable $StageOrders;

    public function initialize(): void
    {
        parent::initialize();
        $this->Rankings = RankingsTable::load();
        $this->Users = UsersTable::load();
        $this->StageOrders = StageOrdersTable::load();
    }

    protected function addNew($data)
    {
        $rankingId = $this->request->getParam('rankingID');
        $eventId = $this->request->getParam('eventID');
        $stageId = $this->request->getParam('stageID');

        $this->Users->getManagerOrFail($this->OAuthServer->getUserID());

        $classes = $this->Rankings->getClassIds($eventId, $stageId, $rankingId);
        if (!$classes) {
            throw new NotFoundException('Classes not found');
        }

        $rk = $this->Rankings->getCached($rankingId);
        $this->StageOrders->getAllCreatingOne($stageId, $rk->getEventId(), $rk->getStageId());

        $loop = Loop::get();
        $browser = new Browser($loop, new Connector());
        $responsesOk = [];
        $responsesErr = [];
        foreach ($classes as $class) {
            $classId = $class->id;
            RankingComputeClassController::rankingRequest(
                $browser,
                "/$rankingId/events/$eventId/stages/$stageId/classes/$classId/compute/"
            )
                ->then(
                    function (Response $response) use ($class, &$responsesOk) {
                        $decodeRes = $this->_decodeRes($response, $class);
                        $responsesOk[] = $decodeRes;
                    },
                    function (\Exception $e) use ($class, &$responsesErr) {
                        if ($e instanceof ResponseException) {
                            $decodeRes = $this->_decodeRes($e->getResponse(), $class);
                            $responsesErr[] = $decodeRes;
                        } else {
                            $responsesErr[] = $e->getMessage();
                        }
                    }
                );
        }

        $loop->run();

        if ($responsesErr) {
            throw new InternalErrorException('Ranking compute errors: ' . json_encode($responsesErr));
        }
        $this->return = $responsesOk;
    }

    private function _decodeRes(ResponseInterface $response, ClassEntity $class): array
    {
        $decodeRes = json_decode($response->getBody()->getContents(), true);
        if (!$decodeRes) {
            $decodeRes = [
                'error' => $response->getBody()->getContents()
            ];
        }
        $decodeRes['class_name'] = $class->short_name;
        return $decodeRes;
    }
}
