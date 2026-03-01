<?php

declare(strict_types = 1);

namespace App\Test\TestCase\Lib\Emails;

use App\Lib\Emails\VerifyEmail;
use Cake\TestSuite\TestCase;

class VerifyEmailTest extends TestCase
{
    public function testEncryptToken(): void
    {
        $token = VerifyEmail::encryptToken(['hello' => 'world']);
        $this->assertNotEmpty($token);
        $this->assertEquals(['hello' => 'world'], VerifyEmail::decryptToken($token));
    }
}
