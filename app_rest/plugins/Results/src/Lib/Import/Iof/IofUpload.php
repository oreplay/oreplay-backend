<?php

declare(strict_types = 1);

namespace Results\Lib\Import\Iof;

use App\Lib\Exception\InvalidPayloadException;
use DateTimeZone;
use Generator;
use Results\Lib\UploadConfigChecker;

/**
 * An IOF XML upload, presented to the import path as the transfer node it already understands.
 *
 * The body is buffered to a temp file once so it can be read more than once — detection, then the
 * streamed walk — without the document ever being held in memory. See docs/upload-xml-input.md 5.6.
 *
 * Two soft limits, both refusing with an explanation rather than letting the request be killed. They are
 * sized from what was measured rather than chosen: see docs/upload-xml-input.md 9.1.
 *
 *  - the largest **class**, because that is what costs memory. One class is held at a time, so 6 000 small
 *    classes cost nothing measurable while entries inside a single class cost ~9 kB each to map and about
 *    3.5x that to import.
 *  - the **body**, because it is held as a string for the whole request, so its size is a floor on usage
 *    whatever the document contains.
 */
class IofUpload
{
    /**
     * ~33 kB per entry end to end against a 128 MB php limit inside a 200 MB container, most of which is
     * already spent on the framework: 2 000 leaves room to fail gracefully rather than at the edge.
     * The largest class in any sampled real event is 226 competitors, so there is ~9x headroom, which is
     * what lets the check be an average rather than an exact maximum.
     */
    public const MAX_AVERAGE_ENTRIES_PER_CLASS = 2000;

    private const CLASS_TAGS = ['<ClassResult', '<ClassStart'];
    private const ENTRY_TAGS = ['<PersonResult', '<PersonStart', '<TeamMemberResult'];

    /**
     * Generous: the largest file in the sampled corpus is 5.5 MB. This bounds only the cost of holding the
     * body, not of importing it.
     */
    public const MAX_BODY_BYTES = 20971520;

    private string $_filePath;
    private IofHeader $_header;

    private function __construct(
        private readonly string $body,
        private readonly string $eventId,
        private readonly string $stageId,
        private readonly DateTimeZone $timeZone,
        ?string $explicitUploadType,
        bool $validateAgainstSchema
    ) {
        $this->_filePath = $this->_bufferToFile($body);
        // the header first: it rejects IOF 2.0 and an unknown root with something an operator can act on,
        // where the schema would only complain about an unexpected namespace
        $this->_header = IofUploadTypeDetector::detect($this->_filePath, $explicitUploadType);
        $this->_refuseAClassTooBigToImport();
        if ($validateAgainstSchema) {
            $this->_refuseUnlessItMatchesTheSchema();
        }
    }

    /**
     * Counted from the raw bytes before anything is parsed, so an oversized class is refused whole rather
     * than importing some classes and then being killed part-way through the offending one.
     *
     * An average rather than the true maximum, which is a deliberate approximation: the shape that runs out
     * of memory is one class holding a whole event, and there the average *is* the maximum because there is
     * only one class. What it misses is one huge class hidden among many small ones, which nothing in the
     * sampled corpus looks like, and the 9x headroom absorbs the rest.
     */
    private function _refuseAClassTooBigToImport(): void
    {
        $average = $this->_averageEntriesPerClass();
        if ($average > self::MAX_AVERAGE_ENTRIES_PER_CLASS) {
            throw new InvalidPayloadException(sprintf(
                'A class of this file holds about %d competitors, over the %d this server can import in one'
                . ' class. Split the largest class, or upload the classes separately.',
                $average,
                self::MAX_AVERAGE_ENTRIES_PER_CLASS
            ));
        }
    }

    private function _averageEntriesPerClass(): int
    {
        $classes = $this->_countOf(self::CLASS_TAGS);
        $entries = $this->_countOf(self::ENTRY_TAGS);
        return $classes ? (int)ceil($entries / $classes) : $entries;
    }

    /**
     * @param string[] $tags
     */
    private function _countOf(array $tags): int
    {
        $found = 0;
        foreach ($tags as $tag) {
            $found += substr_count($this->body, $tag);
        }
        return $found;
    }

    private function _refuseUnlessItMatchesTheSchema(): void
    {
        $errors = IofSchemaValidator::errorsIn($this->_filePath);
        if ($errors) {
            throw new InvalidPayloadException(
                'The XML does not match the IOF 3.0 schema: ' . implode(' | ', $errors));
        }
    }

    public static function fromBody(
        string $body,
        string $eventId,
        string $stageId,
        DateTimeZone $timeZone,
        ?string $explicitUploadType = null,
        bool $validateAgainstSchema = true
    ): self {
        if ($body === '') {
            throw new InvalidPayloadException('The upload body is empty');
        }
        if (!$stageId) {
            throw new InvalidPayloadException(
                'An IOF XML document does not name a stage, so stage_id is required in the query string');
        }
        if (strlen($body) > self::MAX_BODY_BYTES) {
            throw new InvalidPayloadException(sprintf(
                'The XML is %d MB, over the %d MB this server accepts. Export it in parts, by class or'
                . ' by stage.',
                (int)round(strlen($body) / 1048576),
                (int)(self::MAX_BODY_BYTES / 1048576)
            ));
        }
        return new self($body, $eventId, $stageId, $timeZone, $explicitUploadType, $validateAgainstSchema);
    }

    public function getHeader(): IofHeader
    {
        return $this->_header;
    }

    /**
     * What is stored in raw_uploads: the bytes as they arrived, not the mapped array, so a re-upload
     * replays the same document rather than our reading of it.
     */
    public function getRawBody(): string
    {
        return $this->body;
    }

    public function toTransfer(): array
    {
        $configuration = UploadConfigChecker::configurationFor($this->_header->getUploadType());
        return [
            'configuration' => $configuration,
            'event' => [
                'id' => $this->eventId,
                'stages' => [[
                    'id' => $this->stageId,
                    'classes' => $this->_classes(),
                ]],
            ],
        ];
    }

    private function _classes(): Generator
    {
        $isStartList = $this->_header->getRootElement() === 'StartList';
        if ($isStartList) {
            $mapper = new StartListMapper($this->_header, $this->timeZone);
            $classElement = 'ClassStart';
        } else {
            $mapper = new ResultListMapper($this->_header, $this->timeZone);
            $classElement = 'ClassResult';
        }
        foreach ((new IofXmlReader($this->_filePath, $classElement))->classes() as $classResult) {
            yield $mapper->classOf($classResult);
        }
    }

    private function _bufferToFile(string $body): string
    {
        $path = tempnam(sys_get_temp_dir(), 'iof');
        if ($path === false || file_put_contents($path, $body) === false) {
            throw new InvalidPayloadException('Could not buffer the uploaded XML');
        }
        return $path;
    }

    public function __destruct()
    {
        if (isset($this->_filePath) && is_file($this->_filePath)) {
            unlink($this->_filePath);
        }
    }
}
