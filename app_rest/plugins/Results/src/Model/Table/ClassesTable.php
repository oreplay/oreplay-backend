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

    // this is what GET /events/ID/stages/ID/classes returns, and the splits it contains are what the
    // frontend shows as radio controls. It is the read path courses phase 3 replaces with
    // course_controls.is_radio; keep it unchanged until that lands.
    public function getByStageWithRadios(string $eventId, string $stageId)
    {
        $stationsInClass = $this->Splits->getStationsFromLeaderInStage($eventId, $stageId);
        $query = $this->find()->where([
            'event_id' => $eventId,
            'stage_id' => $stageId,
        ])
            ->contain(SplitsTable::name(), function (Query $q) {
                $select = [
                    'class_id',
                    'station',
                    'reading_time'  => $q->func()->min(SplitsTable::field('reading_time'), ['string']),
                    'id' => $q->func()->max(SplitsTable::field('id'), ['string']),
                ];
                // a station only appears here once a runner has punched it, because is_intermediate
                // is set on the split by a Radiocontrols upload, so a class shows no radios until
                // someone reaches one. course_controls.is_radio has to be observed the same way:
                // no production payload carries the radio stations. In the long run we should
                // actually process the radios from the real upload, but keeping in mind some radios
                // could come directly to the server via http direct conection (instead of being
                // uploaded as xml/json via the uploadsController or Uploadsv2)
                // ---
                // class_id
                // order_number
                // station
                // is_intermediate
                return $q
                    ->select($select)
                    ->where([SplitsTable::field('is_intermediate') => true])
                    ->groupBy(['station', 'class_id'])
                    ->orderBy(['station' => 'DESC'], true);
            })
            ->orderBy(['CAST(oe_key AS UNSIGNED)' => 'ASC', 'short_name' => 'ASC']);
        $res = $query->all();
        /** @var ClassEntity $r */
        foreach ($res as $r) {
            $courseStations = $stationsInClass[$r->id] ?? [];
            $r->setSplitsAsSimpleArray($courseStations);
        }
        return $res;
    }

    public function saveManyWithRelations(
        ClassEntity $singleClassToSave,
        SplitsToReplace $splitsToReplace,
        RowsToInsert $rowsToInsert
    ) {
        return $this->getConnection()->transactional(
            fn() => $this->_deleteReplacedSplitsAndSaveNeverRetrying(
                $singleClassToSave,
                $splitsToReplace,
                $rowsToInsert
            )
        );
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
