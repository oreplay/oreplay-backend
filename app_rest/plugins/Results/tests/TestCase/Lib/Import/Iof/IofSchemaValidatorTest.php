<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Import\Iof;

use Cake\TestSuite\TestCase;
use Results\Lib\Import\Iof\IofSchemaValidator;

class IofSchemaValidatorTest extends TestCase
{
    private function _asset(string $name): string
    {
        return dirname(__DIR__, 4) . '/assets/' . $name;
    }

    public function testErrorsIn_shouldFindNothingWrongWithAValidDocument()
    {
        $this->assertEquals([], IofSchemaValidator::errorsIn($this->_asset('iof/splits.xml')));
        $this->assertEquals([], IofSchemaValidator::errorsIn($this->_asset('iof/starts.xml')));
        $this->assertEquals([], IofSchemaValidator::errorsIn($this->_asset('iof/relay.xml')));
    }

    /**
     * Splits_CEEBO.xml is windows-1252, so this also covers the encoding path: libxml transcodes from the
     * declaration during validation as it does during the read.
     */
    public function testErrorsIn_shouldValidateANonUtf8DocumentFromItsDeclaration()
    {
        $this->assertEquals([], IofSchemaValidator::errorsIn($this->_asset('Splits_CEEBO.xml')));
    }

    public function testErrorsIn_shouldReportAnElementTheStandardDoesNotDefine()
    {
        $errors = IofSchemaValidator::errorsIn($this->_asset('iof/invalid_schema.xml'));

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('NotInTheStandard', implode(' ', $errors));
    }

    /**
     * A malformed document has to come back as errors rather than as an exception, so the caller can
     * report them the same way either failure arrives.
     */
    public function testErrorsIn_shouldReportBrokenXmlAsAnError()
    {
        $path = (string)tempnam(sys_get_temp_dir(), 'iof');
        file_put_contents($path, '<?xml version="1.0"?><ResultList><Unclosed></ResultList>');
        try {
            $this->assertNotEmpty(IofSchemaValidator::errorsIn($path));
        } finally {
            unlink($path);
        }
    }

    /**
     * Validation walks the document rather than building it, so it must not grow with the file. Anything
     * that loads a DOM would undo what the streaming reader is for.
     */
    public function testErrorsIn_shouldNotHoldTheDocumentInMemory()
    {
        $path = (string)tempnam(sys_get_temp_dir(), 'iof');
        $valid = file_get_contents($this->_asset('iof/splits.xml'));
        $start = strpos($valid, '<ClassResult>');
        $end = strrpos($valid, '</ClassResult>') + strlen('</ClassResult>');
        file_put_contents($path, substr($valid, 0, $start)
            . str_repeat(substr($valid, $start, $end - $start), 1500) . '</ResultList>');
        try {
            $this->assertGreaterThan(2097152, filesize($path));
            gc_collect_cycles();
            $before = memory_get_usage(true);
            $this->assertEquals([], IofSchemaValidator::errorsIn($path));
            $this->assertLessThan(8 * 1048576, memory_get_usage(true) - $before);
        } finally {
            unlink($path);
        }
    }
}
