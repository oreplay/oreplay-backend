<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class DropDeadStageTimeColumns extends BaseMigration
{
    // Unwritable and unreadable: every one of these is in Stage::$_hidden and none is in $_accessible,
    // and nothing in the codebase ever set or read them. The desktop client does send base_date and
    // base_time in the stage block of every upload, and they were discarded.
    // What supersedes them: events.timezone carries the zone an IOF XML upload needs — a zone name, so it
    // stays right either side of a daylight-saving change, which utc_value's offset would not — and
    // stages.start carries the moment the stage begins, which is what base_date and base_time described.
    public function up(): void
    {
        $table = $this->table('stages');
        $table->removeColumn('base_date');
        $table->removeColumn('base_time');
        $table->removeColumn('server_offset');
        $table->removeColumn('utc_value');
        $table->update();
    }

    public function down(): void
    {
        $table = $this->table('stages');
        $table->addColumn('base_date', 'date', ['null' => true, 'after' => 'description']);
        $table->addColumn('base_time', 'time', ['null' => true, 'after' => 'base_date']);
        $table->addColumn('server_offset', 'integer', ['null' => true, 'default' => 0]);
        $table->addColumn('utc_value', 'string', ['null' => true, 'limit' => 10]);
        $table->update();
    }
}
