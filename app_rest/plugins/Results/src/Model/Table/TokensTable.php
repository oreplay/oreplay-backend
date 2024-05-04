<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\Http\Exception\InternalErrorException;
use Cake\ORM\Behavior\TimestampBehavior;
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

    public function createTokenForEvent(string $eventId, array $data): Token
    {
        /** @var Token $token */
        $token = $this->patchFromNewWithUuid($data);
        $token->foreign_model = 'Event';
        $token->foreign_key = $eventId;
        $token->token = $this->_generateToken();
        $this->saveOrFail($token);
        return $token;
    }

    private function _generateToken(): string
    {
        if (function_exists('random_bytes')) {
            $randomData = random_bytes(20);
            if ($randomData !== false && strlen($randomData) === 20) {
                return bin2hex($randomData);
            }
        }
        throw new InternalErrorException('Cannot generate token');
    }
}
