<?php

declare(strict_types = 1);

namespace Results\Lib\Publish;

use Results\Lib\Import\ClassImportReport;

/**
 * Pushes a class to everyone watching it, as soon as it is committed.
 *
 * Every participant is sent, because one finisher moves everybody's position. Only those whose punches this
 * upload changed carry their splits, and that costs nothing to arrange: the importer attaches splits to a
 * result only when it rewrites them, so a participant it left alone has none to serialise. Splits are 84 %
 * of a participant's payload, so this is most of the traffic.
 *
 * A participant arriving without a `splits` key keeps the splits the reader already has.
 */
class ClassResultsPublisher implements UploadPublisher
{
    public function __construct(
        private readonly ChannelPublisher $channel,
        private readonly ?string $uploadId = null
    ) {
    }

    public function classImported(string $stageId, ClassImportReport $report): void
    {
        $class = $report->getClass();
        if (!$class) {
            return;
        }
        $this->channel->publish(
            'stage/' . $stageId . '/class/' . $report->classId,
            [
                'uploadId' => $this->uploadId,
                'class' => ['id' => $report->classId, 'short_name' => $report->shortName],
                'runners' => $this->_participants($class->runners ?? []),
                'teams' => $this->_participants($class->teams ?? []),
            ]
        );
    }

    private function _participants(array $participants): array
    {
        $payload = [];
        foreach ($participants as $participant) {
            $payload[] = $participant->toArray();
        }
        return $payload;
    }
}
