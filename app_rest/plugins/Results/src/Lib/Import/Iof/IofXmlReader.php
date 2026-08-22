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
 * Walks an IOF document one ClassResult at a time, and inside each one competitor at a time, so memory
 * holds one entry rather than one class. A relay class can be the whole file — the 2025 Jukola export is a
 * single ClassResult of 1 473 teams — and expanding that whole is 150 MB against 22 MB.
 * See docs/upload-xml-input.md 5.6 and 9.2.
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
            while ($reader->read() && $reader->localName !== $this->classElement) {
                continue;
            }
            // not `while ($reader->read())`: expand() does not advance the reader and next() already
            // moves to the following sibling, so reading as well would skip every other class
            while ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === $this->classElement) {
                yield $this->_classResultOf($reader);
                if (!$reader->next($this->classElement)) {
                    break;
                }
            }
        } finally {
            $reader->close();
        }
    }

    private function _classResultOf(XMLReader $reader): IofClassResult
    {
        $data = $this->_attributesOf($reader);
        $children = [];
        $radioStations = [];
        if (!$reader->isEmptyElement) {
            $classDepth = $reader->depth;
            $reader->read();
            while ($reader->depth > $classDepth) {
                if ($reader->nodeType === XMLReader::ELEMENT) {
                    $name = $reader->localName;
                    $document = new DOMDocument();
                    $node = $reader->expand($document);
                    $document->appendChild($node);
                    $isFirstOfItsName = !isset($children[$name]);
                    $children[$name][] = $this->_arrayOf($node, $name);
                    if (!$radioStations && $isFirstOfItsName) {
                        $radioStations = $this->_radioStationsIn($node);
                    }
                    unset($node, $document);
                    if (!$reader->next()) {
                        break;
                    }
                    continue;
                }
                if ($reader->nodeType === XMLReader::COMMENT && !$radioStations) {
                    $radioStations = $this->_stationsInComment((string)$reader->value);
                }
                if (!$reader->read()) {
                    break;
                }
            }
        }
        return new IofClassResult($data + $this->_singlesUnwrapped($children), $radioStations);
    }

    private function _arrayOf(\DOMNode $node, string $name): mixed
    {
        $converted = Xml::toArray(simplexml_import_dom($node));
        return $converted[$name] ?? [];
    }

    /**
     * Searched in the first child of each name only, so a class of 1 473 teams costs three searches rather
     * than 1 473. The comment describes the class and the producers that write one put it in <Class>.
     *
     * @return string[]
     */
    private function _radioStationsIn(\DOMNode $node): array
    {
        foreach ((new DOMXPath($node->ownerDocument))->query('.//comment()', $node) as $comment) {
            $stations = $this->_stationsInComment((string)$comment->nodeValue);
            if ($stations) {
                return $stations;
            }
        }
        return [];
    }

    /**
     * Xml::toArray gives an element that appears once as itself and repeated ones as a list; reading them
     * one at a time cannot tell which it is until the class ends, so the collapse happens here instead.
     */
    private function _singlesUnwrapped(array $children): array
    {
        foreach ($children as $name => $values) {
            if (count($values) === 1) {
                $children[$name] = $values[0];
            }
        }
        return $children;
    }

    private function _attributesOf(XMLReader $reader): array
    {
        $attributes = [];
        if ($reader->hasAttributes && $reader->moveToFirstAttribute()) {
            do {
                $attributes['@' . $reader->localName] = $reader->value;
            } while ($reader->moveToNextAttribute());
            $reader->moveToElement();
        }
        return $attributes;
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
