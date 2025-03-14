<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class AddUploadTypeValues extends AbstractMigration
{
    public function change(): void
    {
        $this->getQueryBuilder()
            ->update('runner_results')
            ->set('upload_type', 'res_splits')
            ->where(['upload_type IS NULL'])
            ->execute();
    }
}
