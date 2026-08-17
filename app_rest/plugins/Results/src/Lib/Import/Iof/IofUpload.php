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
 * There is no limit on the size of the body, because size is not what costs memory: a 20 MB document of
 * 6 000 small classes was measured at no measurable cost, while 12 000 entries inside a *single* class
 * cost 124 MB. What would need bounding is the largest class, not the file.
 */
class IofUpload
{
    private string $_filePath;
    private IofHeader $_header;

    private function __construct(
        private readonly string $body,
        private readonly string $eventId,
        private readonly string $stageId,
        private readonly DateTimeZone $timeZone,
        ?string $explicitUploadType
    ) {
        $this->_filePath = $this->_bufferToFile($body);
        $this->_header = IofUploadTypeDetector::detect($this->_filePath, $explicitUploadType);
    }

    public static function fromBody(
        string $body,
        string $eventId,
        string $stageId,
        DateTimeZone $timeZone,
        ?string $explicitUploadType = null
    ): self {
        if ($body === '') {
            throw new InvalidPayloadException('The upload body is empty');
        }
        if (!$stageId) {
            throw new InvalidPayloadException(
                'An IOF XML document does not name a stage, so stage_id is required in the query string');
        }
        return new self($body, $eventId, $stageId, $timeZone, $explicitUploadType);
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
