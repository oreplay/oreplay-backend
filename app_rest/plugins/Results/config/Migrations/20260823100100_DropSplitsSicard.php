<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class DropSplitsSicard extends BaseMigration
{
    // The chip that punched, written by every upload and every radio punch, and read by nothing: no
    // finder, no query, no raw SQL, and the API never exposed it — Split hides it, and neither split.ts
    // nor v1api.yaml mention it. It duplicates runners.sicard, reachable through splits.runner_id.
    // The two indexes over it went in 20260822100000, and the rows that had nothing but the chip went in
    // the migration before this one.
    public function up(): void
    {
        $table = $this->table('splits');
        $table->removeColumn('sicard');
        $table->update();
    }

    public function down(): void
    {
        $table = $this->table('splits');
        $table->addColumn('sicard', 'string', ['limit' => 10, 'null' => true, 'after' => 'stage_order']);
        $table->update();
    }
}
