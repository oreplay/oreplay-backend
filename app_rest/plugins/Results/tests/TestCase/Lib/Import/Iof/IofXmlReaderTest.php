<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Import\Iof;

use Cake\TestSuite\TestCase;
use Results\Lib\Import\Iof\IofXmlReader;

class IofXmlReaderTest extends TestCase
{
    private function _asset(string $name): string
    {
        return dirname(__DIR__, 4) . '/assets/' . $name;
    }

    private function _reader(string $name): IofXmlReader
    {
        return new IofXmlReader($this->_asset($name));
    }

    public function testClasses_shouldYieldTheContentsOfEachClassResult()
    {
        $classes = iterator_to_array($this->_reader('iof/radio.xml')->classes());

        $this->assertCount(1, $classes);
        $data = $classes[0]->getData();
        $this->assertEquals(['Class', 'Course', 'PersonResult'], array_keys($data));
        $this->assertEquals('E', $data['Class']['ShortName']);
        $this->assertCount(3, $data['PersonResult']);
    }

    /**
     * The radio list SportSoftware writes as an XML comment inside Class. Xml::toArray() drops comments,
     * so it has to be taken from the expanded DOM subtree before conversion.
     */
    public function testClasses_shouldReadTheRadioStationsFromTheComment()
    {
        $classes = iterator_to_array($this->_reader('iof/radio.xml')->classes());

        $this->assertEquals(['32', '34'], $classes[0]->getRadioStations());
    }

    public function testClasses_shouldReportNoRadioStationsWhenTheCommentIsAbsent()
    {
        $classes = iterator_to_array($this->_reader('iof/splits.xml')->classes());

        $this->assertEquals([], $classes[0]->getRadioStations());
    }

    /**
     * expand() does not advance the reader and next() moves to the next sibling, so a `while (read())`
     * loop around them silently processes every *other* ClassResult. This is the guard for that:
     * anything that reads 10 of the 19 classes has the bug.
     */
    public function testClasses_shouldNotSkipEveryOtherClass()
    {
        $names = [];
        foreach ($this->_reader('Splits_CEEBO.xml')->classes() as $class) {
            $names[] = $class->getData()['Class']['ShortName'];
        }

        $this->assertCount(19, $names);
        $this->assertEquals('PreBe', $names[0]);
        $this->assertNotEquals($names[0], end($names));
    }

    /**
     * Splits_CEEBO.xml is windows-1252. libxml transcodes from the declaration, so the class names come
     * back as valid UTF-8 without anyone converting anything by hand — see docs/upload-xml-input.md 5.2.
     */
    public function testClasses_shouldDecodeANonUtf8DocumentFromItsDeclaration()
    {
        $longNames = [];
        foreach ($this->_reader('Splits_CEEBO.xml')->classes() as $class) {
            $longNames[] = $class->getData()['Class']['Name'];
        }

        $this->assertContains('Pre Benjamí', $longNames);
        foreach ($longNames as $name) {
            $this->assertTrue(mb_check_encoding($name, 'UTF-8'), $name . ' is not UTF-8');
        }
    }

    public function testClasses_shouldBeLazySoOneClassIsHeldAtATime()
    {
        $classes = $this->_reader('Splits_CEEBO.xml')->classes();

        $this->assertInstanceOf(\Generator::class, $classes);
        foreach ($classes as $class) {
            $this->assertEquals('PreBe', $class->getData()['Class']['ShortName']);
            break;
        }
    }

    public function testClasses_shouldYieldNothingForADocumentWithoutClasses()
    {
        $this->assertEquals([], iterator_to_array($this->_reader('iof/empty_result_list.xml')->classes()));
    }
}
