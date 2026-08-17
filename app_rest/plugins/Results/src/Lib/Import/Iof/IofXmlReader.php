<?php

declare(strict_types = 1);

namespace Results\Lib\Import\Iof;

use App\Lib\Exception\InvalidPayloadException;
use Cake\Utility\Xml;
use DOMDocument;
use DOMXPath;
use Generator;
use XMLReader;

/**
 * Walks an IOF document one ClassResult at a time, so memory holds one class rather than the whole
 * event. See docs/upload-xml-input.md 5.6 for why this shape and not XML-to-array.
 */
class IofXmlReader
{
    private const RADIO_MARKER = 'SplitTimeControls:';

    /**
     * @param string $classElement ClassResult for a result list, ClassStart for a start list
     */
    public function __construct(
        private readonly string $filePath,
        private readonly string $classElement = 'ClassResult'
    ) {
    }

    /**
     * @return Generator<IofClassResult>
     */
    public function classes(): Generator
    {
        $reader = new XMLReader();
        // the raw bytes go to libxml untouched: it transcodes from the encoding declaration itself, and
        // converting first is what produces silent mojibake
        if (!$reader->open($this->filePath, null, LIBXML_NONET)) {
            throw new InvalidPayloadException('Could not read the uploaded XML');
        }
        try {
            $scratch = new DOMDocument();
            while ($reader->read() && $reader->localName !== $this->classElement) {
                continue;
            }
            // not `while ($reader->read())`: expand() does not advance the reader and next() already
            // moves to the following sibling, so reading as well would skip every other class
            while ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === $this->classElement) {
                yield $this->_classResultOf($reader->expand($scratch), $scratch);
                if (!$reader->next($this->classElement)) {
                    break;
                }
            }
        } finally {
            $reader->close();
        }
    }

    private function _classResultOf(\DOMNode $node, DOMDocument $scratch): IofClassResult
    {
        $data = Xml::toArray(simplexml_import_dom($node));
        return new IofClassResult(
            $data[$this->classElement] ?? [],
            $this->_radioStationsIn($node, $scratch)
        );
    }

    /**
     * @return string[]
     */
    private function _radioStationsIn(\DOMNode $node, DOMDocument $scratch): array
    {
        $xpath = new DOMXPath($node->ownerDocument ?? $scratch);
        foreach ($xpath->query('.//comment()', $node) as $comment) {
            $stations = $this->_stationsInComment((string)$comment->nodeValue);
            if ($stations) {
                return $stations;
            }
        }
        return [];
    }

    /**
     * @return string[]
     */
    private function _stationsInComment(string $comment): array
    {
        $marker = strpos($comment, self::RADIO_MARKER);
        if ($marker === false) {
            return [];
        }
        $listed = substr($comment, $marker + strlen(self::RADIO_MARKER));
        $stations = [];
        foreach (explode(',', $listed) as $station) {
            $station = trim($station);
            if ($station !== '') {
                $stations[] = $station;
            }
        }
        return $stations;
    }
}
