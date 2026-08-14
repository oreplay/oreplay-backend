<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Results\Lib\UploadHelper;

trait UploadHashTrait
{
    public function isSameUploadHash(array $compareArray): bool
    {
        $existingHash = $this->_fields['upload_hash'] ?? null;
        return $existingHash && $existingHash === UploadHelper::md5Encode($compareArray);
    }

    public function setHash(array $resultData)
    {
        $this->_fields['upload_hash'] = UploadHelper::md5Encode($resultData);
        $this->setDirty('upload_hash');
    }
}
