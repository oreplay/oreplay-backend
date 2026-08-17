<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Import\Iof;

use Cake\TestSuite\TestCase;
use DateTimeZone;
use Results\Lib\Import\Iof\IofUpload;

class IofUploadSizeTest extends TestCase
{
    private const CLASSES = 900;

    /**
     * There used to be a 2 MB limit on the body. It was measuring the wrong thing: memory does not grow
     * with the size of the document — 6 000 small classes over 20 MB cost nothing measurable, because one
     * class is held at a time — it grows with the largest single class. A document of many small classes
     * is now accepted however large it is.
     */
    public function testFromBody_shouldAcceptADocumentFarLargerThanTheOldTwoMegabyteLimit()
    {
        $body = $this->_manySmallClasses();
        $this->assertGreaterThan(2097152, strlen($body), 'the body has to exceed the limit that was removed');

        $upload = IofUpload::fromBody($body, 'event-id', 'stage-id', new DateTimeZone('UTC'));
        $classes = $upload->toTransfer()['event']['stages'][0]['classes'];

        $seen = 0;
        foreach ($classes as $class) {
            $seen++;
            $this->assertEquals('E', $class['short_name']);
        }
        $this->assertEquals(self::CLASSES, $seen);
    }

    private function _manySmallClasses(): string
    {
        $splits = file_get_contents(dirname(__DIR__, 4) . '/assets/iof/splits.xml');
        $start = strpos($splits, '<ClassResult>');
        $end = strrpos($splits, '</ClassResult>') + strlen('</ClassResult>');
        $head = substr($splits, 0, $start);
        $oneClass = substr($splits, $start, $end - $start);
        return $head . str_repeat($oneClass, self::CLASSES) . '</ResultList>';
    }
}
