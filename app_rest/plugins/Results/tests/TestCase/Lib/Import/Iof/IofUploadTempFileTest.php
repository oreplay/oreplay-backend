<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Import\Iof;

use App\Lib\Exception\InvalidPayloadException;
use Cake\TestSuite\TestCase;
use DateTimeZone;
use Results\Lib\Import\Iof\IofUpload;

class IofUploadTempFileTest extends TestCase
{
    private function _asset(string $name): string
    {
        return dirname(__DIR__, 4) . '/assets/iof/' . $name;
    }

    /**
     * @return string[]
     */
    private function _bufferedFiles(): array
    {
        return glob(sys_get_temp_dir() . '/iof*') ?: [];
    }

    /**
     * PHP never runs __destruct on an object whose constructor threw, so a refusal raised after the body
     * was buffered has to remove the file itself. Every rejected upload left one behind, including those
     * from requests without a valid token.
     */
    public function testFromBody_shouldRemoveTheBufferedFileOfARejectedDocument()
    {
        foreach (['iof_v2.xml', 'invalid_schema.xml', 'entries.xml'] as $asset) {
            $before = $this->_bufferedFiles();
            try {
                IofUpload::fromBody(file_get_contents($this->_asset($asset)), 'event-id', 'stage-id',
                    new DateTimeZone('UTC'));
                $this->fail($asset . ' was expected to be rejected');
            } catch (InvalidPayloadException $e) {
                $left = array_diff($this->_bufferedFiles(), $before);
                array_map('unlink', $left);
                $this->assertSame([], array_values($left), $asset . ' left its buffered file behind');
            }
        }
    }

    public function testFromBody_shouldRemoveTheBufferedFileOnceTheUploadIsReleased()
    {
        $before = $this->_bufferedFiles();
        $upload = IofUpload::fromBody(file_get_contents($this->_asset('splits.xml')), 'event-id', 'stage-id',
            new DateTimeZone('UTC'));
        $this->assertCount(1, array_diff($this->_bufferedFiles(), $before));

        unset($upload);

        $this->assertSame([], array_values(array_diff($this->_bufferedFiles(), $before)));
    }
}
