<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Import\Iof;

use App\Lib\Exception\InvalidPayloadException;
use Cake\TestSuite\TestCase;
use Results\Lib\Consts\UploadTypes;
use Results\Lib\Import\Iof\IofUploadTypeDetector;

class IofUploadTypeDetectorTest extends TestCase
{
    private function _asset(string $name): string
    {
        return dirname(__DIR__, 4) . '/assets/iof/' . $name;
    }

    /**
     * The rule the Java desktop client uses, see docs/upload-xml-input.md 2B. A radiocontrol export is
     * a splits export plus an XML comment SportSoftware writes inside each Class, and nothing else in
     * the document distinguishes them.
     */
    public function testDetect_shouldTellRadioFromSplitsFromStartsWithoutBeingTold()
    {
        $expected = [
            'starts.xml' => UploadTypes::START_LIST,
            'radio.xml' => UploadTypes::INTERMEDIATES,
            'radio_without_punches.xml' => UploadTypes::INTERMEDIATES,
            'splits.xml' => UploadTypes::SPLITS,
            'totals.xml' => UploadTypes::FINISH_TIMES,
        ];
        foreach ($expected as $asset => $uploadType) {
            $this->assertEquals($uploadType,
                IofUploadTypeDetector::detect($this->_asset($asset))->getUploadType(), $asset);
        }
    }

    public function testDetect_shouldReadTheProducerAndTheVersionFromTheHeader()
    {
        $header = IofUploadTypeDetector::detect($this->_asset('radio.xml'));

        $this->assertStringContainsString('SportSoftware OE12', $header->getCreator());
        $this->assertEquals('3.0', $header->getIofVersion());
        $this->assertEquals('ResultList', $header->getRootElement());
    }

    /**
     * The escape hatch for MeOS, SiTiming and OE2010, none of which writes the comment.
     */
    public function testDetect_shouldLetAnExplicitTypeOverrideTheFile()
    {
        $header = IofUploadTypeDetector::detect($this->_asset('splits.xml'), UploadTypes::INTERMEDIATES);

        $this->assertEquals(UploadTypes::INTERMEDIATES, $header->getUploadType());
        $this->assertNull($header->getWarning());
    }

    /**
     * A ResultList with neither splits nor finish times cannot say what it is: it may be a splits
     * export taken before anyone finished. Guessing silently is what makes the desktop client call it
     * totals, so the guess has to be reported.
     */
    public function testDetect_shouldWarnWhenTheFileCannotSayWhatItIs()
    {
        $header = IofUploadTypeDetector::detect($this->_asset('empty_result_list.xml'));

        $this->assertStringContainsString('could not be determined', $header->getWarning());
        $this->assertStringContainsString('SportSoftware', $header->getWarning());
    }

    public function testDetect_shouldNotWarnWhenTheFileIsUnambiguous()
    {
        $this->assertNull(IofUploadTypeDetector::detect($this->_asset('radio.xml'))->getWarning());
        $this->assertNull(IofUploadTypeDetector::detect($this->_asset('splits.xml'))->getWarning());
    }

    public function testDetect_shouldRejectIofVersion2()
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('Unsupported IOF version 2.0');

        IofUploadTypeDetector::detect($this->_asset('iof_v2.xml'));
    }

    public function testDetect_shouldRejectADocumentThatIsNotAnIofUploadAtAll()
    {
        $path = tempnam(sys_get_temp_dir(), 'iof');
        file_put_contents($path, '<?xml version="1.0"?><CourseData iofVersion="3.0"></CourseData>');

        try {
            $this->expectException(InvalidPayloadException::class);
            $this->expectExceptionMessage('Unsupported IOF root element CourseData');
            IofUploadTypeDetector::detect($path);
        } finally {
            unlink($path);
        }
    }
}
