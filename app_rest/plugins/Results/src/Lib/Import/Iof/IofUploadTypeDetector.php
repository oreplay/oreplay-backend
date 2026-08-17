<?php

declare(strict_types = 1);

namespace Results\Lib\Import\Iof;

use App\Lib\Exception\InvalidPayloadException;
use Results\Lib\Consts\UploadTypes;

/**
 * Decides what an IOF document is without asking the operator. See docs/upload-xml-input.md 2B for the
 * rule this reproduces and 2C for why an explicit flag outranks it.
 */
class IofUploadTypeDetector
{
    private const SUPPORTED_VERSION = '3.0';
    private const HEADER_BYTES = 2000;
    private const CHUNK_BYTES = 16384;

    /**
     * SportSoftware writes this as an XML comment inside each Class of a radiocontrol export. It is not
     * part of IOF, and no other producer writes it, so its absence never proves the opposite.
     */
    private const RADIO_MARKER = 'SplitTimeControls:';

    private const UPLOAD_TYPE_OF_ROOT = [
        'StartList' => UploadTypes::START_LIST,
        'EntryList' => UploadTypes::ENTRY_LIST,
    ];

    public static function detect(string $filePath, ?string $explicitUploadType = null): IofHeader
    {
        $header = self::_readHeader($filePath);
        if ($explicitUploadType) {
            return $header->withUploadType($explicitUploadType);
        }
        return $header;
    }

    private static function _readHeader(string $filePath): IofHeader
    {
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            throw new InvalidPayloadException('Could not read the uploaded XML');
        }
        try {
            $head = (string)fread($handle, self::HEADER_BYTES);
            $rootElement = self::_rootElementOf($head);
            $version = self::_attribute($head, 'iofVersion');
            if ($version !== self::SUPPORTED_VERSION) {
                throw new InvalidPayloadException('Unsupported IOF version ' . ($version ?: 'missing'));
            }
            $creator = self::_attribute($head, 'creator') ?: 'unknown';
            if (isset(self::UPLOAD_TYPE_OF_ROOT[$rootElement])) {
                return new IofHeader($rootElement, $creator, $version, self::UPLOAD_TYPE_OF_ROOT[$rootElement]);
            }
            if ($rootElement !== 'ResultList') {
                throw new InvalidPayloadException('Unsupported IOF root element ' . $rootElement);
            }
            return self::_resultListHeader($handle, $head, $creator, $version);
        } finally {
            fclose($handle);
        }
    }

    private static function _resultListHeader($handle, string $head, string $creator, string $version): IofHeader
    {
        $found = self::_scanFor($handle, $head);
        if ($found[self::RADIO_MARKER]) {
            return new IofHeader('ResultList', $creator, $version, UploadTypes::INTERMEDIATES);
        }
        if ($found['<SplitTime']) {
            return new IofHeader('ResultList', $creator, $version, UploadTypes::SPLITS);
        }
        if ($found['<FinishTime']) {
            return new IofHeader('ResultList', $creator, $version, UploadTypes::FINISH_TIMES);
        }
        return new IofHeader('ResultList', $creator, $version, UploadTypes::FINISH_TIMES,
            'The upload type could not be determined from the file written by ' . $creator
            . ': it carries neither split times nor finish times. Treated as finish times;'
            . ' send the type explicitly to be sure.');
    }

    /**
     * A radiocontrol export is a splits export plus a comment, so the marker has to be looked for in
     * the whole document: finding split times first proves nothing. Reading in chunks keeps a large
     * file out of memory, and the overlap stops a needle from being missed across a chunk boundary.
     *
     * @return array<string, bool>
     */
    private static function _scanFor($handle, string $head): array
    {
        $needles = [self::RADIO_MARKER, '<SplitTime', '<FinishTime'];
        $found = array_fill_keys($needles, false);
        $overlap = max(array_map('strlen', $needles)) - 1;
        $buffer = $head;
        while ($buffer !== '') {
            foreach ($needles as $needle) {
                if (!$found[$needle] && str_contains($buffer, $needle)) {
                    $found[$needle] = true;
                }
            }
            if ($found[self::RADIO_MARKER]) {
                return $found;
            }
            $next = (string)fread($handle, self::CHUNK_BYTES);
            if ($next === '') {
                return $found;
            }
            $buffer = substr($buffer, -$overlap) . $next;
        }
        return $found;
    }

    private static function _rootElementOf(string $head): string
    {
        if (!preg_match('/<([A-Za-z][\w.-]*)[\s>]/', preg_replace('/<\?xml.*?\?>/s', '', $head) ?: '', $match)) {
            throw new InvalidPayloadException('The upload is not an XML document with a root element');
        }
        return $match[1];
    }

    private static function _attribute(string $head, string $name): ?string
    {
        if (!preg_match('/\b' . $name . '\s*=\s*"([^"]*)"/', $head, $match)) {
            return null;
        }
        return $match[1];
    }
}
