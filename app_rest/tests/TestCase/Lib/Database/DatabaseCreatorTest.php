<?php

declare(strict_types = 1);

namespace App\Test\TestCase\Lib\Database;

use App\Lib\Database\DatabaseCreator;
use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Database\Exception\QueryException;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

class DatabaseCreatorTest extends TestCase
{
    private const MISSING_DATABASE = 'app_rest_creator_test';

    public function tearDown(): void
    {
        ConnectionManager::drop(self::MISSING_DATABASE);
        parent::tearDown();
    }

    public function testCreateIfMissing_existingDatabaseIsNotCreated()
    {
        $this->assertFalse(DatabaseCreator::createIfMissing('test'));
    }

    public function testCreateIfMissing_rejectsUnsafeDatabaseName()
    {
        $this->_setConnection('app_rest`; DROP');
        $this->assertFalse(DatabaseCreator::createIfMissing(self::MISSING_DATABASE));
    }

    public function testCreateIfMissing_createsMissingDatabase()
    {
        $this->_skipWithoutCreateDatabaseGrant();
        $this->_setConnection(self::MISSING_DATABASE);
        $this->assertTrue(DatabaseCreator::createIfMissing(self::MISSING_DATABASE));
        $this->assertFalse(DatabaseCreator::createIfMissing(self::MISSING_DATABASE));
        ConnectionManager::get('test')->execute('DROP DATABASE ' . self::MISSING_DATABASE);
    }

    private function _skipWithoutCreateDatabaseGrant(): void
    {
        try {
            ConnectionManager::get('test')->execute('DROP DATABASE IF EXISTS ' . self::MISSING_DATABASE);
        } catch (QueryException $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    private function _setConnection(string $database): void
    {
        $config = ConnectionManager::get('test')->config();
        $config['className'] = Connection::class;
        $config['driver'] = Mysql::class;
        $config['database'] = $database;
        unset($config['name']);
        ConnectionManager::setConfig(self::MISSING_DATABASE, $config);
    }
}
