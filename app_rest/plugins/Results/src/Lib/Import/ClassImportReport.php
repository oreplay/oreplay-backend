<?php

declare(strict_types = 1);

namespace Results\Lib\Import;

use Results\Model\Entity\ClassEntity;

/**
 * What one class import wrote. It is read after the class is committed, so it describes rows that are
 * already visible to a reader, which is what makes it safe to publish.
 */
class ClassImportReport
{
    private function __construct(
        public readonly string $classId,
        public readonly string $shortName,
        public readonly int $runnerCount,
        public readonly int $teamCount,
        private readonly ?ClassEntity $class = null
    ) {
    }

    /**
     * The class as it was committed. Held only for as long as the report is: it is read by whoever is
     * publishing, inside the loop, and both are gone before the next class is read.
     */
    public function getClass(): ?ClassEntity
    {
        return $this->class;
    }

    public static function of(ClassEntity $class): self
    {
        return new self(
            (string)$class->id,
            (string)$class->short_name,
            count($class->runners ?? []),
            count($class->teams ?? []),
            $class
        );
    }

    public function participantCount(): int
    {
        return $this->runnerCount + $this->teamCount;
    }
}
