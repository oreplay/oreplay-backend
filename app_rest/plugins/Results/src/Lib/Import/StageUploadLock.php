<?php

declare(strict_types = 1);

namespace Results\Lib\Import;

use App\Lib\Consts\CacheGrp;
use Cake\Cache\Cache;

/**
 * One import at a time per stage.
 *
 * A client that uploads every few seconds will post again while the previous file is still being imported,
 * and nothing else serialises them: two imports then load the same stored participants, neither sees the
 * other's uncommitted rows, and both create the same runner. Worse, the older one can finish last and write
 * its stale results over the newer ones.
 *
 * Refusing the second is better than queueing it, because the client's next file is seconds away and
 * carries fresher data than anything waiting in a queue would.
 *
 * The lock lives in the cache rather than a column: `add()` is atomic on both the memcached and the redis
 * engines, and the entry expires by itself, so a request killed mid-import cannot block a stage for ever.
 */
class StageUploadLock
{
    private ?string $_key = null;

    public function acquire(string $eventId, string $stageId): bool
    {
        $key = $this->_keyOf($eventId, $stageId);
        if (!Cache::add($key, true, CacheGrp::UPLOAD_LOCK)) {
            return false;
        }
        $this->_key = $key;
        return true;
    }

    /**
     * Releasing is what makes the expiry a backstop rather than the mechanism: an upload that finishes in
     * two seconds must not hold the stage for the whole timeout.
     */
    public function release(): void
    {
        if ($this->_key !== null) {
            Cache::delete($this->_key, CacheGrp::UPLOAD_LOCK);
            $this->_key = null;
        }
    }

    private function _keyOf(string $eventId, string $stageId): string
    {
        return 'stage_' . $eventId . '_' . $stageId;
    }
}
