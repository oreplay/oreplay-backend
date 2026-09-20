<?php

declare(strict_types = 1);

namespace App\Test\TestCase\Lib;

use App\Lib\ConnectedDatabase;
use Cake\TestSuite\TestCase;

class ConnectedDatabaseTest extends TestCase
{
    public function testLabel_shouldCallEveryDevelopmentHostLocal()
    {
        foreach (['mysql', 'localhost', '127.0.0.1', 'host.docker.internal'] as $host) {
            $connected = new ConnectedDatabase('mysql://root:password@' . $host . ':3306/app_rest');

            $this->assertEquals(ConnectedDatabase::LOCAL, $connected->label(), $host);
        }
    }

    public function testLabel_shouldCallAnyOtherHostRemote()
    {
        $connected = new ConnectedDatabase('mysql://admin:secret@o-replay2024.mysql.database.azure.com:3306/app_rest');

        $this->assertEquals(ConnectedDatabase::REMOTE, $connected->label());
    }

    public function testLabel_shouldCallAnUnreadableUrlRemote()
    {
        $this->assertEquals(ConnectedDatabase::REMOTE, (new ConnectedDatabase(''))->label(),
            'without a url there is no proof of being local, and the safe answer is the one that '
            . 'refuses a request meant for development');
    }
}
