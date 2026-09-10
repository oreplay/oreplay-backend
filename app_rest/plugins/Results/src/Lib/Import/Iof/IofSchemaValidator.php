<?php

declare(strict_types = 1);

namespace Results\Lib\Import\Iof;

use XMLReader;

/**
 * Checks a document against the IOF v3.0 schema.
 *
 * Validation happens *during* a streaming read rather than over a loaded DOM, which is what keeps it from
 * undoing the reason IofXmlReader streams at all: measured at no growth in memory for a 13 MB document,
 * against roughly 50 ms per MB.
 *
 * On unless `?validate=false` asks otherwise. The escape hatch exists because real files fail for reasons
 * that do not stop a correct import: of the 141 sampled exports, 132 validate and 4 of the 9 failures are
 * nothing but a producer writing `Creator` where the standard says `creator`. See docs/upload-xml-input.md 9.
 */
class IofSchemaValidator
{
    private const MAX_REPORTED = 10;

    private static function _schemaPath(): string
    {
        return dirname(__DIR__, 4) . '/resources/IOF.xsd';
    }

    /**
     * @return string[] empty when the document is valid
     */
    public static function errorsIn(string $filePath): array
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        try {
            return self::_readReportingErrors($filePath);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private static function _readReportingErrors(string $filePath): array
    {
        $reader = new XMLReader();
        if (!$reader->open($filePath, null, LIBXML_NONET)) {
            return ['Could not read the uploaded XML'];
        }
        try {
            if (!$reader->setSchema(self::_schemaPath())) {
                return self::_messagesOf(libxml_get_errors()) ?: ['Could not load the IOF schema'];
            }
            // @ suppresses the notice libxml raises per invalid node; the errors are collected instead
            while (@$reader->read()) {
                continue;
            }
            return self::_messagesOf(libxml_get_errors());
        } finally {
            $reader->close();
        }
    }

    /**
     * One malformed class can produce an error per row, so the list is capped: it is there to tell an
     * operator what is wrong with their file, not to reproduce libxml's whole output.
     *
     * @param \LibXMLError[] $errors
     * @return string[]
     */
    private static function _messagesOf(array $errors): array
    {
        $messages = [];
        foreach ($errors as $error) {
            $message = 'line ' . $error->line . ': ' . trim($error->message);
            $messages[$message] = $message;
            if (count($messages) >= self::MAX_REPORTED) {
                break;
            }
        }
        return array_values($messages);
    }
}
