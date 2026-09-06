<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class DropRunnersUploadHash extends BaseMigration
{
    // Added in 20241120224145 beside classes.upload_hash and runner_results.upload_hash, which both carry a
    // comment saying what they skip; this one carries none and never got a writer. It could not have had
    // one: Runner puts it in $_hidden and never in $_accessible, so it was never assignable, and Runner
    // does not use UploadHashTrait either. The live hashes stay on classes, courses, runner_results and
    // team_results.
    public function up(): void
    {
        $table = $this->table('runners');
        $table->removeColumn('upload_hash');
        $table->update();
    }

    public function down(): void
    {
        $table = $this->table('runners');
        $table->addColumn('upload_hash', 'string', ['null' => true, 'after' => 'leg_number']);
        $table->update();
    }
}
