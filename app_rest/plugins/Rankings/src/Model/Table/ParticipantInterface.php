<?php

declare(strict_types = 1);

namespace Rankings\Model\Table;

use Results\Model\Entity\Club;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\TeamResult;

interface ParticipantInterface
{
    public function _getStage(): TeamResult|RunnerResult|null;
    public function setLeader(ParticipantInterface $runner): ParticipantInterface;
    public function _getRankingPoints(): ?float;
    public function _getClub(): ?Club;
    public function getResultList();
    public function toArrayWithoutID(): array;
    public function isStatusOk(): bool;
    public function isLeader(): bool;
}
