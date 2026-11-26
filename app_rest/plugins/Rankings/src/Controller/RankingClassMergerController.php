<?php

declare(strict_types = 1);

namespace Rankings\Controller;

use App\Model\Table\UsersTable;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Rankings\Model\Table\RankingsTable;
use Results\Controller\ApiController;
use Results\Model\Entity\ClassEntity;
use Results\Model\Table\RunnersTable;

class RankingClassMergerController extends ApiController
{
    private RankingsTable $Rankings;
    private RunnersTable $Runners;

    public function isPublicController(): bool
    {
        return false;
    }

    public function initialize(): void
    {
        parent::initialize();
        $this->Rankings = RankingsTable::load();
        $this->Runners = RunnersTable::load();
    }

    protected function beforeMain($id = null, $secondParam = null)
    {
        UsersTable::load()->getManagerOrFail($this->OAuthServer->getUserID());
        return null;
    }

    /**
     * Merging deletes runners and the source class, so it is a POST: a GET must stay safe to repeat, prefetch
     * or retry
     */
    protected function addNew($data)
    {
        $fromShortName = (string)($data['from_class'] ?? '');
        $toShortName = (string)($data['to_class'] ?? '');
        if ($fromShortName === '' || $toShortName === '') {
            throw new BadRequestException('Both from_class and to_class are mandatory');
        }
        $rk = $this->Rankings->getCached($this->request->getParam('rankingID'));

        $eventId = $rk->getEventId();
        $stageId = $rk->getStageId();
        $fromClass = $this->Runners->Classes->getByShortName($eventId, $stageId, $fromShortName);
        $toClass = $this->Runners->Classes->getByShortName($eventId, $stageId, $toShortName);
        if (!$fromClass) {
            throw new NotFoundException('Class from not found ' . $fromShortName);
        }
        if (!$toClass) {
            throw new NotFoundException('Class to not found ' . $toShortName);
        }
        $this->_assertDifferentClasses($fromClass, $toClass);

        $connection = $this->Runners->getConnection();
        $matchedRunners = $connection->transactional(function () use ($eventId, $stageId, $fromClass, $toClass) {
            $runFrom = $this->Runners->findRunnersInStage($eventId, $stageId, ['class_id' => $fromClass->id])
                ->toArray();
            $runTo = $this->Runners->findRunnersInStage($eventId, $stageId, ['class_id' => $toClass->id])->toArray();
            // this can result in copying one runner from one class to another where he participated, but not the target
            $matched = $this->Runners->moveRunnerResultsFromClassTo($runFrom, $runTo);
            $this->Runners->moveRunnersFromClassTo($fromClass->id, $toClass->id);
            $this->Runners->Classes->softDelete($fromClass->id);
            return $matched;
        });
        $this->return = [
            'runnersMatched' => $matchedRunners,
            'from' => $fromClass,
            'to' => $toClass,
            'ranking' => $rk,
        ];
    }

    /**
     * Merging a class into itself matches every runner with itself, which soft-deletes all of them and then the
     * class
     */
    private function _assertDifferentClasses(ClassEntity $fromClass, ClassEntity $toClass): void
    {
        if ($fromClass->id === $toClass->id) {
            throw new BadRequestException('from_class and to_class must be different classes');
        }
    }
}
