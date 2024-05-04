<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\Utility\Text;
use Results\Model\Entity\Token;

/**
 * @property EventsTable $Events
 */
class TokensTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        EventsTable::addHasMany($this);
    }

    public function createTokenForEvent(string $eventId): Token
    {
        /** @var Token $token */
        $token = $this->patchFromNewWithUuid(['foreign_model' => 'Event']);
        $token->foreign_model = 'Event';
        $token->foreign_key = $eventId;
        $token->token = Text::uuid(); // todo change this
        $this->saveOrFail($token);
        return $token;
    }
}
