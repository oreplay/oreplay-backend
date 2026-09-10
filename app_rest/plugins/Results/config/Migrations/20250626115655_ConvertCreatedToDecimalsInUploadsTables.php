<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class ConvertCreatedToDecimalsInUploadsTables extends BaseMigration
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
