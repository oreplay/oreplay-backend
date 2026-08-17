<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Import\Iof;

use Cake\TestSuite\TestCase;
use DateTimeZone;
use Results\Lib\Import\Iof\IofUploadTypeDetector;
use Results\Lib\Import\Iof\IofXmlReader;
use Results\Lib\Import\Iof\ResultListMapper;

class ResultListMapperTest extends TestCase
{
    /**
     * The event was timed in Spain and the XML carries local times with no offset, so this is what the
     * Java client's own machine applied when it produced the expected payload.
     */
    private const EVENT_TIME_ZONE = 'Europe/Madrid';

    /**
     * The reference payload was produced by an older build of the desktop client, before it learned to
     * emit these. They are asserted separately rather than dropped from the mapper.
     */
    private const NEWER_THAN_THE_REFERENCE = ['db_id', 'contributory'];

    private function _asset(string $name): string
    {
        return dirname(__DIR__, 4) . '/assets/' . $name;
    }

    private function _mappedClasses(string $asset): array
    {
        $header = IofUploadTypeDetector::detect($this->_asset($asset));
        $mapper = new ResultListMapper($header, new DateTimeZone(self::EVENT_TIME_ZONE));
        $mapped = [];
        foreach ((new IofXmlReader($this->_asset($asset)))->classes() as $class) {
            $mapped[] = $mapper->classOf($class);
        }
        return $mapped;
    }

    private function _withoutNewerKeys(array $classes): array
    {
        foreach ($classes as &$class) {
            foreach ($class['runners'] as &$runner) {
                foreach (self::NEWER_THAN_THE_REFERENCE as $key) {
                    unset($runner[$key]);
                }
                foreach ($runner['runner_results'] as &$result) {
                    foreach (self::NEWER_THAN_THE_REFERENCE as $key) {
                        unset($result[$key]);
                    }
                }
            }
        }
        return $classes;
    }

    /**
     * The golden pair: Splits_CEEBO.xml, already in this directory, and the oreplay_data_transfer the
     * Java desktop client produced from that same file. This pins the whole mapping against the
     * reference implementation rather than against a reading of its source — 19 classes, 194 runners,
     * 3 347 splits.
     *
     * Splits_CEEBO.json is *not* the expected side: it is the same XML converted naively to JSON, so it
     * is the input in another shape.
     */
    public function testClassOf_shouldReproduceWhatTheDesktopClientProduces()
    {
        $expectedClasses = json_decode(file_get_contents($this->_asset('Splits_CEEBO-oreplay.json')), true);

        $actual = $this->_withoutNewerKeys($this->_mappedClasses('Splits_CEEBO.xml'));

        $this->assertCount(count($expectedClasses), $actual);
        foreach ($expectedClasses as $i => $expectedClass) {
            $this->assertEquals($expectedClass, $actual[$i], 'class ' . $expectedClass['short_name']);
        }
    }

    /**
     * A class with a single PersonResult whose Result has a single SplitTime: Xml::toArray() gives those
     * as objects where every other class gives lists.
     */
    public function testClassOf_shouldHandleASingleRepeatedElementAsAListOfOne()
    {
        $org = null;
        foreach ($this->_mappedClasses('Splits_CEEBO.xml') as $class) {
            if ($class['short_name'] === 'ORG') {
                $org = $class;
            }
        }

        $this->assertCount(1, $org['runners']);
        $this->assertCount(1, $org['runners'][0]['runner_results']);
        $this->assertCount(1, $org['runners'][0]['runner_results'][0]['splits']);
        $this->assertEquals('200', $org['runners'][0]['runner_results'][0]['splits'][0]['station']);
    }

    public function testClassOf_shouldReconstructAbsolutePunchTimesFromElapsedSeconds()
    {
        $classes = $this->_mappedClasses('Splits_CEEBO.xml');
        $result = $classes[0]['runners'][0]['runner_results'][0];

        $this->assertEquals('2024-11-17T11:17:49.000+01:00', $result['start_time']);
        $this->assertEquals(142, $result['splits'][0]['time_seconds']);
        $this->assertEquals('2024-11-17T11:20:11.000+01:00', $result['splits'][0]['reading_time']);
        $this->assertEquals(1731838811000, $result['splits'][0]['reading_milli']);
    }

    public function testClassOf_shouldKeepAMissingSplitWithoutAnyTime()
    {
        $missing = [];
        foreach ($this->_mappedClasses('Splits_CEEBO.xml') as $class) {
            foreach ($class['runners'] as $runner) {
                foreach ($runner['runner_results'] as $result) {
                    foreach ($result['splits'] as $split) {
                        if ($split['status'] === 'Missing') {
                            $missing[] = $split;
                        }
                    }
                }
            }
        }

        $this->assertNotEmpty($missing);
        foreach ($missing as $split) {
            $this->assertArrayNotHasKey('reading_time', $split);
            $this->assertArrayNotHasKey('reading_milli', $split);
            $this->assertArrayNotHasKey('time_seconds', $split);
            $this->assertNotEmpty($split['station']);
            $this->assertGreaterThan(0, $split['order_number']);
        }
    }

    public function testClassOf_shouldCarryTheRadioStationsOfTheClassThroughUnchanged()
    {
        $header = IofUploadTypeDetector::detect($this->_asset('iof/radio.xml'));
        $mapper = new ResultListMapper($header, new DateTimeZone(self::EVENT_TIME_ZONE));
        foreach ((new IofXmlReader($this->_asset('iof/radio.xml')))->classes() as $class) {
            $mapped = $mapper->classOf($class);
            $this->assertEquals('E', $mapped['short_name']);
            $this->assertEquals(['32', '34'], $class->getRadioStations());
        }
    }
}
