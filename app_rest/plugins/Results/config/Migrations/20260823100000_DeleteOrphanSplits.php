<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class DeleteOrphanSplits extends BaseMigration
{
    // Punches whose chip matched no start list were stored with no owner at all. Nothing ever reconnected
    // them: no query looks a split up by its chip, and the API does not expose one. ProcessPunches now
    // logs such a punch instead of storing it, so this clears what the old behaviour left behind and
    // nothing new arrives.
    //
    // A split with no runner is NOT enough to call it an orphan: a relay leg is stored against the team,
    // with team_id and team_result_id set and runner_id null. Deleting on runner_id alone would take
    // every relay split with it. Belonging to nobody means all four are empty.
    //
    // Deliberately irreversible: the rows carry a chip number that the next migration removes anyway.
    public function up(): void
    {
        $this->execute(
            'DELETE FROM splits'
            . ' WHERE runner_id IS NULL AND team_id IS NULL'
            . ' AND runner_result_id IS NULL AND team_result_id IS NULL'
        );
    }

    public function down(): void
    {
    }
}
