<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class AddUploadTypeToTeamsResults extends AbstractMigration
{
    public function change(): void
    {
        $tableName = 'team_results';
        $table = $this->table($tableName);
        $table->addColumn('upload_type', 'string', [
            'length' => 255,
            'null' => true,
            'after' => 'finish_time'
        ]);
        $table->update();

        $this->getQueryBuilder('update')
            ->update($tableName)
            ->set('upload_type', 'res_splits')
            ->where(['upload_type IS NULL'])
            ->execute();
    }
}
