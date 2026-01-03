<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use App\Lib\FullBaseUrl;
use Cake\I18n\FrozenTime;
use Rankings\Model\Table\ParticipantInterface;
use Rankings\Model\Traits\ParticipantTrait;
use RestApi\Lib\Exception\DetailedException;
use Results\Lib\Consts\StatusCode;
use Results\Lib\Consts\UploadTypes;
use Results\Lib\Output\DuplicatedRunners;
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
 * @property bool $is_nc
 * @property RunnerResult[] $runner_results
 */
class Runner extends AppEntity implements ParticipantInterface
{
    use ParticipantTrait;

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
        'stage',
        'overalls',
    ];

    protected $_hidden = [
        'event_id',
        'stage_id',
        'first_name',
        'last_name',
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
     * @param RunnerResult[] $existingResults
     * @return $this
     */
    public function removeAllExistingResults(array $existingResults): static
    {
        foreach ($existingResults as $existingResult) {
            $existingResult->setSoftDeleted();
            $this->addRunnerResult($existingResult);
        }
        return $this;
    }

    public function addClub(Club $club): Runner
    {
        $this->club = $club;
        $this->_fields['club'] = $club;
        return $this;
    }

    public function _getClub(): ?Club
    {
        return $this->_fields['club'] ?? null;
    }

    public function _getStage(): ?RunnerResult
    {
        /** @var RunnerResult $res */
        try {
            $res = ResultsFilter::getFirstStage($this->getResultList());
            if ($res) {
                $res->cleanSplitsWithoutRadios();
            } else if (!$this->_getOveralls()) {
                $res = new RunnerResult();
                //$res->id = '';
                $res->result_type_id = ResultType::EMPTY;
                //$res->start_time = '';
                //$res->finish_time = '';
                $res->upload_type = UploadTypes::ENTRY_LIST; // could use also UploadTypes::START_LIST
                $res->status_code = StatusCode::DNS; // could also use StatusCode::OK
                $res->time_seconds = 0;
                $res->position = 0;
                $res->is_nc = false;
                $res->time_behind = 0;
                //$res->created = '';
                $res->splits = [];
                return $res;
            }
            return $res;
        } catch (\Exception $e) {
            debug($this->first_name . ' ' . $this->last_name . ' ' . $this->bib_number);
            throw $e;
        }
    }

    /**
     * @return RunnerResult[]
     */
    public function getResultList()
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

    public function isSameDbIdOrBib(Runner $tmpRunner): bool
    {
        $tmpRunnerArray = [
            'db_id' => $tmpRunner->db_id,
            'bib_number' => $tmpRunner->bib_number,
        ];
        $isSameDbId = $this->isSameField('db_id', $tmpRunnerArray);
        $isSameBib = $this->isSameField('bib_number', $tmpRunnerArray);
        return $isSameDbId || $isSameBib;
    }

    public function getMatchedRunner(array $runnerData, ClassEntity $class = null): ?Runner
    {
        if ($this->leg_number) {
            $leg = $runnerData['runner_results'][0]['leg_number'] ?? null;
            if ($leg && $leg != $this->leg_number) {
                return null;
            }
        }
        if ($this->isSameField('db_id', $runnerData)) {
            return $this;
        }
        if ($this->isSameField('bib_number', $runnerData)) {
            return $this;
        }
        $stName = $runnerData['first_name'] ?? null;
        $lastName = $runnerData['last_name'] ?? null;
        if (!$stName && !$lastName) {
            $fullName = '';
        } else {
            $fullName = implode(' ', [$stName, $lastName]);
        }
        if ($fullName) {
            if ($fullName == implode(' ', [$this->first_name, $this->last_name])) {
                return $this->isSameClass($class);
            } else {
                return null;
            }
        } else {
            /*
            $legNumber = $runnerData['runner_results'][0]['leg_number'] ?? 0;
            if ($legNumber > 0 && in_array($lastName, ['nn', 'N.N.'])) {
                // we should allow any relay runner to be empty (but leg has to be defined)
                return null;
            }
            //*/
            $copied = unserialize(serialize($runnerData));
            unset($copied['club']);
            unset($copied['stage']);
            unset($copied['runner_results']);
            unset($copied['team_results']);
            unset($copied['overalls']);
            $msg = "Fields first_name [$stName] and last_name [$lastName] cannot be empty "
                . 'when bib_number and db_id is also empty '
                . json_encode($copied);
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

    public function toArrayWithoutID(): array
    {
        $participant = $this->jsonSerialize();
        $participant['id'] = '';
        $participant['first_name'] = $this->first_name;
        $participant['last_name'] = $this->last_name;
        return $participant;
    }

    public function toSimpleDeduplicationArray(): array
    {
        $id = $this->id;
        $event = $this->event_id;
        $stage = $this->stage_id;
        $param = DuplicatedRunners::PARAM_REMOVE_FROM_RANKING;
        $parts = $this->_getOveralls()?->_getParts();
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'bib_number' => $this->bib_number,
            //'db_id' => $this->db_id,
            'class' => $this->class?->short_name,
            'partials_size' => count((array)$parts),
            //'position' => $this->_getOveralls()?->_getOverall()?->position,
            'points' => $this->_getOveralls()?->_getOverall()?->points_final,
            'invalidate_link' => FullBaseUrl::host()
                . "/api/v1/events/$event/stages/$stage/results?output=DuplicatedRunners&simplify=true&$param=$id",
        ];
    }
}
