<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Cake\ORM\Entity;
use RestApi\Lib\Exception\DetailedException;

/**
 * @property string $first_name
 * @property string $last_name
 * @property Club $club
 * @property string $event_id
 * @property string $stage_id
 * @property string $class_id
 * @property string $db_id
 * @property mixed $sicard
 * @property mixed $bib_number
 */
class Runner extends Entity
{
    public const FIRST_RUNNER = 'd08fa43b-ddf8-47f6-9a59-2f1828881765';

    protected $_accessible = [
        '*' => false,
        'id' => false,
        'first_name' => true,
        'last_name' => true,
        'sicard' => true,
        'bib_number' => true,
    ];

    protected $_virtual = [
    ];

    protected $_hidden = [
        'event_id',
        'stage_id',
        'uuid',
        'db_id',
        'iof_id',
        'bib_alt',
        'sicard_alt',
        'license',
        'national_id',
        'birth_date',
        'sex',
        'telephone1',
        'telephone2',
        'email',
        'user_id',
        'class_id',
        'class_uuid',
        'club_id',
        'team_id',
        'leg_number',
        'upload_hash',
        'created',
        'modified',
        'deleted',
    ];

    public function addRunnerResult(RunnerResult $runnerResult): Runner
    {
        if (!isset($this->runner_results)) {
            $this->runner_results = [];
        }
        $this->runner_results[] = $runnerResult;
        return $this;
    }
    /**
     * @return RunnerResult[]
     */
    public function getRunnerResults()
    {
        return $this->runner_results;
    }

    public function getMatchedRunner(array $runnerData, ClassEntity $class = null): ?Runner
    {
        if ($this->db_id == $runnerData['db_id']) {
            return $this;
        }
        if ($this->bib_number == $runnerData['bib_number']) {
            return $this;
        }
        $siCard = $runnerData['sicard'] ?? null;
        $stName = $runnerData['first_name'] ?? null;
        $lastName = $runnerData['last_name'] ?? null;
        if ($siCard && $stName && $lastName) {
            if ($this->first_name == $stName && $this->last_name == $lastName && $this->sicard == $siCard) {
                if ($class) {
                    if ($this->class_id == $class->id) {
                        return $this;
                    } else {
                        return null;
                    }
                } else {
                    return $this;
                }
            } else {
                return null;
            }
        } else {
            throw new DetailedException('Fields sicard, first_name and last_name cannot be empty');
        }
    }

    public function getMatchedRunnerWithoutSportIdent(array $runnerData, ClassEntity $class = null): ?Runner
    {
        $stName = $runnerData['first_name'] ?? null;
        $lastName = $runnerData['last_name'] ?? null;
        if ($this->first_name == $stName && $this->last_name == $lastName) {
            // If not found by name and SportIdent, we match without SI (in case runner changed SI) #37 bHrt3cTU
            if ($class) {
                if ($this->class_id == $class->id) {
                    return $this;
                } else {
                    return null;
                }
            } else {
                return $this;
            }
        } else {
            return null;
        }
    }
}
