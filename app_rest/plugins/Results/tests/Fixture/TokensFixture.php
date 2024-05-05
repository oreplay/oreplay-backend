<?php

declare(strict_types = 1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\Event;

class TokensFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Results.Tokens';
    public const FIRST_TOKEN = '8fe6cd50c76d54a4e6c3b30e6b724a86703d8178';
    public const FIRST_ID = 'fa349e58-00b4-4db5-8e2e-ce25e7669adf';

    public $records = [
        [
            'id' => TokensFixture::FIRST_ID,
            'foreign_model' => 'Event',
            'foreign_key' => Event::FIRST_EVENT,
            'token' => TokensFixture::FIRST_TOKEN,
            'expires' => '2036-05-05 10:00:08',
            'created' => '2024-05-05 10:00:08',
            'modified' => '2024-05-05 10:00:08',
            'deleted' => null,
        ]
    ];
}
