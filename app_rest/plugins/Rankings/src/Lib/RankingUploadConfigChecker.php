<?php

declare(strict_types = 1);

namespace Rankings\Lib;

use Rankings\Model\Entity\Ranking;
use Results\Lib\Consts\UploadTypes;
use Results\Lib\UploadConfigChecker;

class RankingUploadConfigChecker extends UploadConfigChecker
{
    private Ranking $ranking;

    public function __construct(Ranking $ranking)
    {
        $this->ranking = $ranking;
    }

    public function isTotals(): bool
    {
        return true;
    }

    public function getStageId(): string
    {
        return $this->ranking->getStageId();
    }

    public function preCheckType(): string
    {
        return UploadTypes::TOTAL_POINTS;
    }
}
