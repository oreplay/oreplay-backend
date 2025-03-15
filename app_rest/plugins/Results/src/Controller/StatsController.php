<?php

declare(strict_types = 1);

namespace Results\Controller;

use Results\Model\Table\RunnerResultsTable;

/**
 * @property RunnerResultsTable $RunnerResults
 */
class StatsController extends ApiController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->RunnerResults = RunnerResultsTable::load();
    }

    public function isPublicController(): bool
    {
        return true;
    }

    private function _getClassParam(string $param, string $default): array
    {
        $officialSub20M = $this->request->getQueryParams()[$param] ?? '';
        if (!$officialSub20M) {
            $officialSub20M = $default;
        }
        return explode(',', $officialSub20M);
    }

    public function getList()
    {
        $eventId = $this->request->getParam('eventID');
        $stageId = $this->request->getParam('stageID');
        $officialSub20M = $this->_getClassParam('officialSub20M', 'M-15,M-17,M-20,M-21');
        $officialSub20F = $this->_getClassParam('officialSub20F', 'F-15,F-17,F-20,F-21');
        $females = 'F-E,F-35,F-40,F-45,F-50,F-55,F-60,F-65,F-70,F-75,F-80,F-85,F-90,F-95';
        $officialSeniorF = $this->_getClassParam('officialSeniorF', $females);
        $males = 'M-E,M-35,M-40,M-45,M-50,M-55,M-60,M-65,M-70,M-75,M-80,M-85,M-90,M-95';
        $officialSeniorM = $this->_getClassParam('officialSeniorM', $males);
        $all = array_merge($officialSub20M, $officialSub20F, $officialSeniorM, $officialSeniorF);

        $table = $this->RunnerResults;
        $this->return = [
            'officialSub20' => [
                'M' => $table->getClassesStats($eventId, $stageId, $officialSub20M),
                'F' => $table->getClassesStats($eventId, $stageId, $officialSub20F),
            ],
            'officialSenior' => [
                'M' => $table->getClassesStats($eventId, $stageId, $officialSeniorM),
                'F' => $table->getClassesStats($eventId, $stageId, $officialSeniorF),
            ],
            'others' => [
                'M' => $table->getNotClassesStats($eventId, $stageId, $all, 'M'),
                'F' => $table->getNotClassesStats($eventId, $stageId, $all, 'F'),
                'any' => $table->getNotClassesStats($eventId, $stageId, $all, ''),
            ],
        ];
    }
}
