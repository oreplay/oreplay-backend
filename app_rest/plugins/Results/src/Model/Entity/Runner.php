<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Cake\I18n\FrozenTime;
use RestApi\Lib\Exception\DetailedException;
use Results\Lib\ResultsFilter;

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
 * @property string $sex
 * @property FrozenTime $created
 * @property mixed $leg_number
 * @property RunnerResult[] $runner_results
 */
class Runner extends AppEntity
{
    public const FIRST_RUNNER = 'd08fa43b-ddf8-47f6-9a59-2f1828881765';

    protected $_accessible = [
        '*' => false,
        'id' => false,
        'first_name' => true,
        'last_name' => true,
        'sicard' => true,
        'bib_number' => true,
        'sex' => true,
        'leg_number' => true,
        'is_nc' => true,
        'eligibility' => true,
    ];

    protected $_virtual = [
        'full_name',
        'overall',
        'stage',
        'overalls',
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
        'telephone1',
        'telephone2',
        'email',
        'user_id',
        'class_id',
        'class_uuid',
        'club_id',
        'team_id',
        'runner_results',
        'upload_hash',
        'results',
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

    public function addClub(Club $club): Runner
    {
        $this->club = $club;
        return $this;
    }

    public function _getOveralls(): ?array
    {
        return ResultsFilter::getOveralls($this->getRunnerResults());
    }

    public function _getStage(): ?RunnerResult
    {
        /** @var RunnerResult $res */
        try {
            $res = ResultsFilter::getFirstStage($this->getRunnerResults());
            if ($res) {
                $res->cleanSplitsWithoutRadios();
            }
            return $res;
        } catch (\Exception $e) {
            debug($this->first_name . ' ' . $this->last_name . ' ' . $this->bib_number);
            throw $e;
        }
    }
    /**
     * TO remove after added to frontend
     * @deprecated use _getStage())
     */
    public function _getOverall(): ?RunnerResult
    {
        return $this->_getStage();
    }

    /**
     * @return RunnerResult[]
     */
    public function getRunnerResults()
    {
        return $this->runner_results;
    }

    private function isAnonymous(): bool
    {
        return $this->created && $this->created->lessThan(new FrozenTime('-1 year'));
    }

    public function _getFullName()
    {
        $isAnonymous = $this->isAnonymous();
        $res = [];
        if ($this->first_name) {
            if ($isAnonymous) {
                $res[] = mb_substr($this->first_name, 0, 1) . '.';
            } else {
                $res[] = $this->first_name;
            }
        }
        if ($this->last_name) {
            if ($isAnonymous) {
                $res[] = mb_substr($this->last_name, 0, 1) . '.';
            } else {
                $res[] = $this->last_name;
            }
        }
        return implode(' ', $res);
    }

    public function getMatchedRunner(array $runnerData, ClassEntity $class = null): ?Runner
    {
        $dbId = $runnerData['db_id'] ?? null;
        if ($this->db_id && $this->db_id == $dbId) {
            return $this;
        }
        $bibNumber = $runnerData['bib_number'] ?? null;
        if ($this->bib_number && $this->bib_number == $bibNumber) {
            return $this;
        }
        $stName = $runnerData['first_name'] ?? null;
        $lastName = $runnerData['last_name'] ?? null;
        if ($stName && $lastName) {
            if ($this->first_name == $stName && $this->last_name == $lastName) {
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
            $legNumber = $runnerData['runner_results'][0]['leg_number'] ?? 0;
            if ($legNumber > 0 && in_array($lastName, ['nn', 'N.N.'])) {
                // we should allow any relay runner to be empty (but leg has to be defined)
                return null;
            }
            $msg = "Fields first_name [$stName] and last_name [$lastName] cannot be empty";
            throw new DetailedException($msg);
        }
    }

    //public function getMatchedRunnerWithoutSportIdent(array $runnerData, ClassEntity $class = null): ?Runner
    //{
    //    $stName = $runnerData['first_name'] ?? null;
    //    $lastName = $runnerData['last_name'] ?? null;
    //    if ($this->first_name == $stName && $this->last_name == $lastName) {
    //        // If not found by name and SportIdent, we match without SI (in case runner changed SI) #37 bHrt3cTU
    //        if ($class) {
    //            if ($this->class_id == $class->id) {
    //                return $this;
    //            } else {
    //                return null;
    //            }
    //        } else {
    //            return $this;
    //        }
    //    } else {
    //        return null;
    //    }
    //}
}
