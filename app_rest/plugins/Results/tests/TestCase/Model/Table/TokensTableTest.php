<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Results\Model\Entity\Event;
use Results\Model\Table\TokensTable;
use Results\Test\Fixture\TokensFixture;

class TokensTableTest extends TestCase
{
    protected $fixtures = [
        TokensFixture::LOAD,
    ];
    private TokensTable $Tokens;

    public function setUp(): void
    {
        parent::setUp();
        $this->Tokens = TokensTable::load();
    }

    public function testCreateTokenForEvent()
    {
        $data = [
            'expires' => '2014-07-06T13:09:01',
        ];
        $res = $this->Tokens->createTokenForEvent(Event::FIRST_EVENT, $data);
        $this->assertEquals('Event', $res->foreign_model);
        $this->assertEquals(Event::FIRST_EVENT, $res->foreign_key);
        $this->assertEquals('2014-07-06T13:09:01+00:00', $res->expires->toIso8601String());
        $this->assertEquals(8, strlen($res->token));
    }
}
