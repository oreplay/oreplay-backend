<?php

declare(strict_types = 1);

namespace App\Test\TestCase\Lib;

use App\Lib\Consts\CacheGrp;
use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;

/**
 * The upload cache is invalidated with clearGroup and never with clear. MemcachedEngine::clear()
 * deletes only the keys that Memcached::getAllKeys() reports, and that listing does not include
 * recently written ones, so it silently succeeds without deleting anything. These tests pin the
 * behaviour so a memcached or cache engine upgrade that changes it fails here instead of in
 * production, where it would show up as an upload reading entities of the previous upload.
 */
class UploadCacheInvalidationTest extends TestCase
{
    private const KEY = 'uploadCacheInvalidationProbe';

    public function tearDown(): void
    {
        Cache::delete(self::KEY, CacheGrp::UPLOAD);
        parent::tearDown();
    }

    public function testClearGroupRemovesUploadKeys()
    {
        Cache::write(self::KEY, 'cached', CacheGrp::UPLOAD);
        $this->assertEquals('cached', Cache::read(self::KEY, CacheGrp::UPLOAD));

        Cache::clearGroup(CacheGrp::UPLOAD_ENTITIES_GROUP, CacheGrp::UPLOAD);

        $this->assertNull(Cache::read(self::KEY, CacheGrp::UPLOAD),
            'clearGroup must invalidate the upload cache, see UploadsV2Controller::_clearUploadCache()');
    }

    public function testUploadConfigDeclaresTheGroupClearGroupNeeds()
    {
        $groups = Cache::getConfig(CacheGrp::UPLOAD)['groups'] ?? [];

        $this->assertContains(CacheGrp::UPLOAD_ENTITIES_GROUP, $groups,
            'without the group the key carries no generation and clearGroup becomes a silent no-op');
    }
}
