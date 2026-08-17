<?php

declare(strict_types = 1);

namespace Results\Lib\Import\Iof;

use App\Lib\Exception\InvalidPayloadException;
use DateTimeZone;
use Results\Lib\UploadConfigChecker;
use Results\Lib\UploadHelper;
use Results\Lib\UploadMetrics;
use Results\Model\Table\EventsTable;

/**
 * Turns an IOF XML body into the UploadHelper the import path consumes, so a controller only has to read
 * the request and hand the pieces over.
 *
 * It holds on to the IofUpload it built, because the classes are streamed lazily from that document's
 * buffered file: releasing it would pull the file out from under the import.
 */
class IofUploadFactory
{
    private ?IofUpload $_upload = null;

    public function __construct(private readonly UploadMetrics $metrics)
    {
    }

    public function helperFor(string $body, IofUploadOptions $options): UploadHelper
    {
        $this->_upload = IofUpload::fromBody(
            $body,
            $options->eventId,
            $options->stageId,
            $this->_timeZoneOf($options),
            $options->uploadType
        );
        $this->_warnAbout($this->_upload->getHeader());
        $transfer = $this->_upload->toTransfer();
        $helper = new UploadHelper($transfer, $options->eventId, $this->metrics);
        $helper->setConfigChecker(UploadConfigChecker::fromTransfer($transfer));
        $helper->setRawBody($this->_upload->getRawBody());
        return $helper;
    }

    private function _warnAbout(IofHeader $header): void
    {
        $warning = $header->getWarning();
        if ($warning) {
            $this->metrics->setWarning($warning);
        }
    }

    /**
     * IOF carries local times with no offset, so something has to say which zone they are in: reading them
     * in the wrong one shifts every time in the event, and changes the upload hash, so the same event
     * uploaded as XML and as JSON would stop matching.
     *
     * events.timezone holds a zone *name* rather than an offset, so it stays right either side of a
     * daylight-saving change, which a stored "+01:00" would not.
     */
    private function _timeZoneOf(IofUploadOptions $options): DateTimeZone
    {
        if ($options->timeZoneName) {
            return $this->_timeZoneNamed($options->timeZoneName);
        }
        $ofEvent = EventsTable::load()->getTimezone($options->eventId);
        if ($ofEvent) {
            return $this->_timeZoneNamed($ofEvent);
        }
        return $this->_timeZoneOfLastResort();
    }

    private function _timeZoneOfLastResort(): DateTimeZone
    {
        $fallback = date_default_timezone_get();
        $this->metrics->setWarning('The event has no time zone, so the times in the XML were read as '
            . $fallback . '. Set the event time zone, or send tz in the query string.');
        return new DateTimeZone($fallback);
    }

    private function _timeZoneNamed(string $name): DateTimeZone
    {
        try {
            return new DateTimeZone($name);
        } catch (\Throwable $e) {
            throw new InvalidPayloadException('Unknown time zone ' . $name);
        }
    }
}
