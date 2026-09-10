<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class AddUploadTypeValues extends BaseMigration
{
    public function change(): void
    {
        $this->getQueryBuilder('update')
            ->update('runner_results')
            ->set('upload_type', 'res_splits')
            ->where(['upload_type IS NULL'])
            ->execute();
    }
}
