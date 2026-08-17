<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Import\Iof;

use Cake\TestSuite\TestCase;
use DateTimeZone;
use App\Lib\Exception\InvalidPayloadException;
use Results\Lib\Import\Iof\IofUpload;

class IofUploadSizeTest extends TestCase
{
    private const CLASSES = 900;

    /**
     * What is bounded is the largest class, not the document: 6 000 small classes over 20 MB cost nothing
     * measurable, because one class is held at a time. A large event of ordinary classes has to be accepted.
     */
    public function testFromBody_shouldAcceptAManyClassDocumentFarLargerThanTwoMegabytes()
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

    /**
     * The shape that would otherwise be killed part-way through: jukola puts 5 892 competitors in a single
     * ClassResult and needed 171 MB, against a 128 MB php limit inside a 200 MB container. Refusing it says
     * what is wrong; running out of memory does not.
     */
    public function testFromBody_shouldRefuseAClassWithMoreCompetitorsThanItCanImport()
    {
        $body = $this->_oneClassOf(IofUpload::MAX_AVERAGE_ENTRIES_PER_CLASS + 1);

        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessageMatches('/holds about 2001 competitors, over the 2000/');

        IofUpload::fromBody($body, 'event-id', 'stage-id', new DateTimeZone('UTC'), null, false);
    }

    public function testFromBody_shouldAcceptAClassRightUpToTheLimit()
    {
        $body = $this->_oneClassOf(IofUpload::MAX_AVERAGE_ENTRIES_PER_CLASS);

        $upload = IofUpload::fromBody($body, 'event-id', 'stage-id', new DateTimeZone('UTC'), null, false);

        $this->assertEquals('ResultList', $upload->getHeader()->getRootElement());
    }

    public function testFromBody_shouldRefuseABodyOverTheServerLimit()
    {
        $body = str_pad($this->_oneClassOf(1), IofUpload::MAX_BODY_BYTES + 1, ' ');

        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessageMatches('/over the 20 MB this server accepts/');

        IofUpload::fromBody($body, 'event-id', 'stage-id', new DateTimeZone('UTC'), null, false);
    }

    private function _oneClassOf(int $entries): string
    {
        $splits = file_get_contents(dirname(__DIR__, 4) . '/assets/iof/splits.xml');
        $start = strpos($splits, '<ClassResult>');
        $firstPerson = strpos($splits, '<PersonResult>');
        preg_match('/<PersonResult>.*?<\/PersonResult>/s', $splits, $person);
        return substr($splits, 0, $firstPerson)
            . str_repeat($person[0], $entries) . '</ClassResult></ResultList>';
    }

    /**
     * The approximation is an average, so a large event of ordinary classes has to stay acceptable however
     * many competitors it holds in total: 900 classes of 3 average 3, not 2 700.
     */
    public function testFromBody_shouldJudgeTheAverageAndNotTheTotalNumberOfCompetitors()
    {
        $body = $this->_manySmallClasses();
        $total = substr_count($body, '<PersonResult');

        $upload = IofUpload::fromBody($body, 'event-id', 'stage-id', new DateTimeZone('UTC'), null, false);

        $this->assertGreaterThan(IofUpload::MAX_AVERAGE_ENTRIES_PER_CLASS, $total,
            'the total has to exceed the per-class limit for this to prove anything');
        $this->assertEquals('ResultList', $upload->getHeader()->getRootElement());
    }
}
