<?php

declare(strict_types = 1);

namespace Results\Controller;

use RestApi\Model\Entity\RestApiEntity;
use Results\Model\Table\RunnerResultsTable;

class FedoStatsController extends ApiController
{
    private RunnerResultsTable $RunnerResults;

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
        $officialSub20M = $this->_getClassParam('officialSub20M', 'U-10,M-12,M-14,M-16,M-16/18,M-18E,M-18,M-20E,M-20');
        $officialSub20F = $this->_getClassParam('officialSub20F', 'U-10,F-12,F-14,F-16,F-16/18,F-18E,F-18,F-20E,F-20');
        $females = 'F-E,F-21A,F-21B,F-35,F-35B,F-35A,F-40,F-45,F-50,F-55,F-60,F-65,F-70,F-75,F-80,F-85,F-90,F-95';
        $officialSeniorF = $this->_getClassParam('officialSeniorF', $females);
        $males = 'M-E,M-21A,M-21B,M-35,M-35B,M-35A,M-40,M-45,M-50,M-55,M-60,M-65,M-70,M-75,M-80,M-85,M-90,M-95';
        $officialSeniorM = $this->_getClassParam('officialSeniorM', $males);
        $officialSub20 = array_merge($officialSub20M, $officialSub20F);
        $officialSenior = array_merge($officialSeniorM, $officialSeniorF);
        $all = array_merge($officialSub20, $officialSenior);

        $table = $this->RunnerResults;
        $this->return = [
            RestApiEntity::CLASS_NAME => 'FedoStats',
            'officialSub20' => [
                RestApiEntity::CLASS_NAME => 'GenderGrouped',
                'M' => $table->getFedoClassesStats($eventId, $stageId, $officialSub20M, 'M'),
                'F' => $table->getFedoClassesStats($eventId, $stageId, $officialSub20F, 'F'),
                'any' => $table->getFedoClassesStats($eventId, $stageId, $officialSub20, ''),
            ],
            'officialSenior' => [
                RestApiEntity::CLASS_NAME => 'GenderGrouped',
                'M' => $table->getFedoClassesStats($eventId, $stageId, $officialSeniorM, 'M'),
                'F' => $table->getFedoClassesStats($eventId, $stageId, $officialSeniorF, 'F'),
                'any' => $table->getFedoClassesStats($eventId, $stageId, $officialSenior, ''),
            ],
            'others' => [
                RestApiEntity::CLASS_NAME => 'GenderGrouped',
                'M' => $table->getNotClassesStats($eventId, $stageId, $all, 'M'),
                'F' => $table->getNotClassesStats($eventId, $stageId, $all, 'F'),
                'any' => $table->getNotClassesStats($eventId, $stageId, $all, ''),
            ],
        ];
    }
}
