<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\Datasource\EntityInterface;
use Cake\Log\LogTrait;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query;
use Results\Lib\Import\RowsToInsert;
use Results\Lib\Import\SplitsToReplace;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Control;

/**
 * @property RunnersTable $Runners
 * @property TeamsTable $Teams
 * @property CoursesTable $Courses
 * @property SplitsTable $Splits
 */
class ClassesTable extends AppTable
{
    use LogTrait;
    protected ?string $_entityClass = ClassEntity::class;

    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        RunnersTable::addBelongsTo($this);
        TeamsTable::addBelongsTo($this);
        CoursesTable::addHasMany($this);
        SplitsTable::addBelongsTo($this)->setSort(SplitsTable::defaultOrder());
    }

    public static function load(): self
    {
        /** @var ClassesTable $table */
        $table = parent::load();
        return $table;
    }

    public function duplicateIfNotExists(string $classId, string $eventId, string $stageId): ClassEntity
    {
        /** @var ClassEntity $class */
        $class = $this->get($classId);
        $classObj = [
            'id' => '',
            'uuid' => $classId,
            'oe_key' => $class->oe_key,
            'short_name' => $class->short_name,
            'long_name' => $class->long_name,
        ];
        return $this->createIfNotExists($eventId, $stageId, $classObj);
    }

    public function createIfNotExists(string $eventId, string $stageId, array $data): ClassEntity
    {
        /** @var ClassEntity $class */
        $class = parent::createIfNotExists($eventId, $stageId, $data);
        return $class;
    }

    public function getByShortName(string $eventId, string $stageId, string $shortName): ?ClassEntity
    {
        /** @var ClassEntity $res */
        $res = parent::getByShortName($eventId, $stageId, $shortName);
        return $res;
    }

    // this is what GET /events/ID/stages/ID/classes returns, and the radios it contains are what the
    // frontend shows as radio controls
    public function getAllInStage(string $eventId, string $stageId)
    {
        return $this->find()
            ->where(['event_id' => $eventId, 'stage_id' => $stageId])
            ->orderBy(['CAST(oe_key AS UNSIGNED)' => 'ASC', 'short_name' => 'ASC'])
            ->all();
    }

    public function getByStageWithRadios(string $eventId, string $stageId)
    {
        $classes = $this->getAllInStage($eventId, $stageId);
        $stationsByCourse = CourseControlsTable::load()->stationsByCourseInStage($stageId);
        $radios = ControlsTable::load()->intermediateInStage($stageId);
        /** @var ClassEntity $class */
        foreach ($classes as $class) {
            $courseStations = $stationsByCourse[$class->course_id ?? ''] ?? [];
            $class->splits = $this->_radiosInCourseOrder($courseStations, $radios);
        }
        return $classes;
    }

    /**
     * @param string[] $courseStations
     * @param Control[] $radios keyed by station
     * @return Control[]
     */
    private function _radiosInCourseOrder(array $courseStations, array $radios): array
    {
        $inOrder = [];
        foreach ($courseStations as $station) {
            if (isset($radios[$station])) {
                $inOrder[] = $radios[$station];
            }
        }
        return $inOrder;
    }

    public function saveManyWithRelations(
        ClassEntity $singleClassToSave,
        SplitsToReplace $splitsToReplace,
        RowsToInsert $rowsToInsert
    ) {
        $saved = $this->getConnection()->transactional(
            fn() => $this->_deleteReplacedSplitsAndSaveNeverRetrying(
                $singleClassToSave,
                $splitsToReplace,
                $rowsToInsert
            )
        );
        return $saved;
    }

    // warning: never call this from a retry such as saveOrFailRetrying(). deleteAndForget() consumes
    // the collected ids, so a rollback undoes the deletes while the collection is already empty and
    // the second attempt would save the new splits next to the stored ones it was meant to replace.
    private function _deleteReplacedSplitsAndSaveNeverRetrying(
        ClassEntity $singleClassToSave,
        SplitsToReplace $splitsToReplace,
        RowsToInsert $rowsToInsert
    ) {
        $splitsToReplace->deleteAndForget(SplitsTable::load());
        $saved = $this->saveManyOrFail([$singleClassToSave], ['associated' => $this->_associationsSavedByOrm()]);
        $rowsToInsert->insertAndForget(ControlsTable::load(), SplitsTable::load());
        return $saved;
    }

    private function _associationsSavedByOrm(): array
    {
        return $this->_associationsExcept('Splits', $this, []);
    }

    private function _associationsExcept(string $skip, \Cake\ORM\Table $table, array $path): array
    {
        $found = [];
        foreach ($table->associations() as $association) {
            $name = $association->getName();
            if ($name === $skip || in_array($name, $path, true)) {
                continue;
            }
            $found[$name] = ['associated' => $this->_associationsExcept(
                $skip,
                $association->getTarget(),
                array_merge($path, [$name])
            )];
        }
        return $found;
    }

    public function saveOrFailRetrying(ClassEntity $class): EntityInterface
    {
        $maxRetries = 5;
        $attempt = 0;
        do {
            try {
                return $this->getConnection()->transactional(function () use ($class) {
                    return $this->saveOrFail($class);
                });
            } catch (\PDOException $e) {
                $this->log('Error saving, will retry: ' . $e->getCode() . ' ' . $e->getMessage());
                if ($e->getCode() === '40001' && $attempt < $maxRetries) {
                    $attempt++;
                    $around100ms = random_int(81000, 102000);
                    usleep($around100ms);
                } else {
                    throw $e;
                }
            }
        } while ($attempt < $maxRetries);
    }

}
