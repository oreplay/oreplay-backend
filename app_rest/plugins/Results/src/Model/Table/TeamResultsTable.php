<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Behavior\TimestampBehavior;
use Results\Lib\UploadHelper;
use Results\Model\Entity\Team;
use Results\Model\Entity\TeamResult;

/**
 * @property ResultTypesTable $ResultTypes
 * @property TeamsTable $Teams
 * @property SplitsTable $Splits
 */
class TeamResultsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        TeamsTable::addBelongsTo($this);
        SplitsTable::addBelongsTo($this)->setSort(SplitsTable::defaultOrder());
        ResultTypesTable::addHasMany($this);
    }

    public static function load(): self
    {
        /** @var TeamResultsTable $table */
        $table = parent::load();
        return $table;
    }

    protected function _insert(EntityInterface $entity, array $data)
    {
        return parent::_insert($entity, $data);
    }

    public function fillNewWithStage(array $data, string $eventId, string $stageId): TeamResult
    {
        /** @var TeamResult $res */
        $res = parent::fillNewWithStage($data, $eventId, $stageId);
        return $res;
    }

    public function getAllResults(UploadHelper $helper): ResultSetInterface
    {
        return $this->findWhereEventAndStage($helper)
            ->orderAsc('team_id')
            ->all();
    }

    private function _newResultWithType(array $resultData, UploadHelper $helper): TeamResult
    {
        $resultToSave = $this->fillNewWithStage($resultData, $helper->getEventId(), $helper->getStageId());
        $resultToSave->upload_type = $helper->getChecker()->preCheckType();

        $resultToSave->result_type = $this->ResultTypes
            ->getCachedWithDefault($helper->getChecker(), $resultData['result_type']['id'] ?? null);

        return $resultToSave;
    }

    public function createTeamResult(array $resultData, Team $team, UploadHelper $helper): Team
    {
        $helper->getMetrics()->startRunnerResultsTime();
        $teamResultToSave = $this->_newResultWithType($resultData, $helper);

        $existingTeamResults = $helper->getExistingDbResultsForThisTeam($team, $teamResultToSave);
        $existingTeamResultsAmount = count($existingTeamResults);
        if ($existingTeamResultsAmount) {
            if ($existingTeamResultsAmount === 1) {
                // if there is only one existing result, we reuse the ID to replace the db row
                $teamResultToSave->setIDsToUpdate($existingTeamResults[0]);
            } else {
                // if there is more than one result, we keep them all in the runner
                foreach ($existingTeamResults as $existingResult) {
                    $team = $team->addTeamResult($existingResult);
                }
            }
        }
        $helper->getMetrics()->endRunnerResultsTime();

        $splits = $resultData['splits'] ?? [];
        /** @var TeamResult $teamResultToSave */
        $teamResultToSave = $this->Splits->uploadAllSplits($splits, $teamResultToSave, $helper); // NOT TESTED
        // needs testing in UploadsControllerTest
        return $team->addTeamResult($teamResultToSave);
    }
}
