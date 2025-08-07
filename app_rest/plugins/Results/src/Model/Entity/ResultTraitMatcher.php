<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Results\Lib\Consts\UploadTypes;
use Results\Lib\UploadHelper;

trait ResultTraitMatcher
{
    public function getId(): string
    {
        return $this->id;
    }

    public function isSameResult(ParticipantResultsEntity $runnerResultToSave): bool
    {
        if ($this->leg_number != $runnerResultToSave->leg_number) {
            return false;
        }
        if ($this->result_type_id != $runnerResultToSave->result_type->id) {
            return false;
        }
        return true;
    }

    public function isIntermediates(): bool
    {
        return $this->upload_type === UploadTypes::INTERMEDIATES;
    }

    public function isSplits(): bool
    {
        return $this->upload_type === UploadTypes::SPLITS;
    }

    public function setIDsToUpdate(TeamResult | RunnerResult $oldResult): static
    {
        $this->id = $oldResult->id;
        $this->upload_hash = $oldResult->upload_hash;
        $this->setDirty('upload_hash');
        return $this;
    }

    public function setHash(array $resultData)
    {
        $hash = UploadHelper::md5Encode($resultData);
        $this->_fields['upload_hash'] = $hash;
        $this->setDirty('upload_hash');
    }

    public function hasSameSplits(array $compareArray): bool
    {
        $uploadHash = UploadHelper::md5Encode($compareArray);
        $existingHash = $this->_fields['upload_hash'] ?? 'hash_run_res_does_not_exist';
        return $existingHash == $uploadHash;
    }

    public function addSplit(Split $split)
    {
        if (!($this->_fields['splits'] ?? null)) {
            $this->replaceSplits([]);
        }
        $this->setDirty('splits');
        $this->_fields['splits'][] = $split;
    }

    public function setSoftDeleted(): void
    {
        $this->deleted = new FrozenTime();
    }
}
