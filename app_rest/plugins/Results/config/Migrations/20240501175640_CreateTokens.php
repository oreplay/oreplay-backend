<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class CreateTokens extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('tokens', ['id' => false]);
        $table
            ->addColumn('id', 'string', [
                'length' => 36,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('foreign_model', 'string', [
                'length' => 36,
                'null' => false,
            ])
            ->addColumn('foreign_key', 'string', [
                'length' => 36,
                'null' => true,
            ])
            ->addColumn('token', 'string', [
                'length' => 255,
                'null' => false,
            ])
            ->addColumn('expires', 'timestamp', [
                'length' => 3,
                'null' => false,
            ])
            ->addColumn('created', 'timestamp', [
                'length' => 3,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'length' => 3,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'length' => 3,
                'null' => true,
            ])
            ->addIndex(['token']);
        $table->create();
    }
}
