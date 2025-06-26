<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class ConvertCreatedToDecimalsInUploadsTables extends AbstractMigration
{
    public function change(): void
    {
        $this->table('raw_uploads')
            ->changeColumn('created', 'timestamp', [
                'precision' => 3,
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->changeColumn('modified', 'timestamp', [
                'precision' => 3,
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->changeColumn('deleted', 'timestamp', [
                'precision' => 3,
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->update();

        $this->table('upload_logs')
            ->changeColumn('created', 'timestamp', [
                'precision' => 3,
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->changeColumn('modified', 'timestamp', [
                'precision' => 3,
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->changeColumn('deleted', 'timestamp', [
                'precision' => 3,
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->update();
    }
}
