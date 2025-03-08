<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\I18n\FrozenTime;
use Cake\ORM\Behavior\TimestampBehavior;
use Results\Lib\StrGenerator;
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

    public static function load(): self
    {
        /** @var TokensTable $table */
        $table = parent::load();
        return $table;
    }

    public function createTokenForEvent(string $eventId, array $data): Token
    {
        /** @var Token $token */
        $token = $this->patchFromNewWithUuid($data);
        $token->foreign_model = 'Event';
        $token->foreign_key = $eventId;
        $token->token = StrGenerator::generate();
        $this->saveOrFail($token);
        return $token;
    }

    public function findTokensForEvent(string $eventId)
    {
        return $this->find()->where([
            'expires >' => new FrozenTime(),
        ])
            ->innerJoin(['Events' => 'events'], [
                'Events.id = Tokens.foreign_key',
                'Events.id' => $eventId,
                'Tokens.foreign_model = "Event"',
            ]);
    }

    private function getTokenForEvent(string $token, string $eventId): Token
    {
        /** @var Token $token */
        $token = $this->find()->where([
            'token' => $token,
            'expires >' => new FrozenTime(),
        ])
            ->innerJoin(['Events' => 'events'], [
                'Events.id = Tokens.foreign_key',
                'Events.id' => $eventId,
                'Tokens.foreign_model = "Event"',
            ])->firstOrFail();

        return $token;
    }

    public function expireTokenForEvent(string $token, string $eventId): Token
    {
        $token = $this->getTokenForEvent($token, $eventId);
        $token->expires = new FrozenTime();
        $token->deleted = $token->expires;
        /** @var Token $token */
        $token = $this->saveOrFail($token);
        return $token;
    }

    public function isValidEventToken(string $eventId, string $token = null): bool
    {
        if (!$token) {
            return false;
        }
        try {
            $this->getTokenForEvent($token, $eventId);
            return true;
        } catch (RecordNotFoundException $e) {
            return false;
        }
    }
}
