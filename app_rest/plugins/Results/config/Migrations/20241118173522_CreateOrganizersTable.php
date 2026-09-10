<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class CreateOrganizersTable extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('organizers', ['id' => false]);
        $table
            ->addColumn('id', 'string', [
                'length' => 36,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('external_id', 'string', [
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('name', 'string', [
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('country', 'string', [
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('region', 'string', [
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'precision' => 3,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'precision' => 3,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'precision' => 3,
                'null' => true,
            ])
            ->create();
    }
}
