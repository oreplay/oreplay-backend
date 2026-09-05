<?php

declare(strict_types = 1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\Event;

class TokensFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Results.Tokens';
    public const FIRST_TOKEN = 'bBMEWb';
    public const FIRST_ID = 'fa349e58-00b4-4db5-8e2e-ce25e7669adf';
    public const EXPIRED_TOKEN = 'eXpIrD';
    public const EXPIRED_ID = 'c2a4dc10-3b7e-4c02-9f4c-8b2f0a1d6e33';

    public array $records = [
        [
            'id' => TokensFixture::FIRST_ID,
            'foreign_model' => 'Event',
            'foreign_key' => Event::FIRST_EVENT,
            'token' => TokensFixture::FIRST_TOKEN,
            'expires' => '2036-05-05 10:00:08',
            'created' => '2024-05-05 10:00:08',
            'modified' => '2024-05-05 10:00:08',
            'deleted' => null,
        ],
        [
            'id' => TokensFixture::EXPIRED_ID,
            'foreign_model' => 'Event',
            'foreign_key' => Event::FIRST_EVENT,
            'token' => TokensFixture::EXPIRED_TOKEN,
            'expires' => '2024-06-05 10:00:08',
            'created' => '2024-05-05 10:00:08',
            'modified' => '2024-05-05 10:00:08',
            'deleted' => null,
        ],
    ];
}
