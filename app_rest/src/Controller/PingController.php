<?php

declare(strict_types = 1);

namespace App\Controller;

use App\Lib\Consts\CacheGrp;
use App\Lib\Database\DatabaseCreator;
use App\Lib\I18n\LegacyI18n;
use Cake\Cache\Cache;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\FrozenTime;
use Migrations\Migrations;
use RestApi\Lib\RestMigrator;

class PingController extends ApiController
{
    const SECRET = 'pong';

    public function isPublicController(): bool
    {
        return true;
    }

    protected function getMandatoryParams(): array
    {
        return [];
    }

    protected function getData($id)
    {
        if ($id >= 400 && $id < 600) {
            throw new BadRequestException('Rendering exception', $id);
        }
        if ($id != self::SECRET) {
            throw new BadRequestException('Invalid ping');
        }
        Cache::write('testingCachePing', 'hello-cache-ping', CacheGrp::DEFAULT);
        if (Cache::read('testingCachePing') == 'hello-cache-ping') {
            $cache = 'use cache';
        } else {
            $cache = 'errorCache';
        }
        $toRet = [
            '0' => LegacyI18n::getLocale(),
            '1' => $_SERVER['HTTP_HOST'] ?? '',
            '2' => env('APPLICATION_ENV', ''),
            '3' => $cache,
            '4' => new FrozenTime(),
            '5' => env('TEST_ENV', ''),
            '6' => env('TAG_VERSION', ''),
            '7' => SwaggerJsonController::version(),
            '8' => $this->_phpRuntime(),
        ];

        $migrationList = migrationList();
        if ($this->request->getQuery('migrations') !== 'false') {
            DatabaseCreator::createIfMissing();
            try {
                RestMigrator::runMigrations($migrationList, $toRet);
            } catch (\Throwable $e) {
                $this->_rollbackAbandonedTransaction();
                throw $e;
            }
            if ($this->request->getQuery('seeds') !== 'false') {
                $this->_runMainSeed($migrationList);
            }
        }
        $this->return = $toRet;
    }

    private function _phpRuntime(): array
    {
        return [
            'xd' => extension_loaded('xdebug') ? ((string)ini_get('xdebug.mode') ?: 'off') : 'nl',
            'ml' => (string)ini_get('memory_limit'),
        ];
    }

    private function _runMainSeed(array $migrationList)
    {
        $migrations = new Migrations();
        foreach ($migrationList as $plugin) {
            try {
                $migrations->seed($plugin);
            } catch (\Throwable $e) {
                $this->_rollbackAbandonedTransaction();
                throw $e;
            }
        }
    }

    private function _rollbackAbandonedTransaction(): void
    {
        $connection = ConnectionManager::get('default');
        if ($connection->inTransaction()) {
            $connection->rollback();
        }
    }
}
