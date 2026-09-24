<?php

declare(strict_types = 1);

namespace Results\Lib\ClubMatcher;

use RestApi\Lib\Exception\DetailedException;

class IncludedOrganizerFields
{
    private const ORGANIZER_FIELD_BY_INCLUDE = [
        'club.region' => 'region',
        'club.province' => 'province',
        'club.city' => 'city',
    ];

    private function __construct(private readonly array $fieldNames)
    {
    }

    public static function fromQueryValue(mixed $include): self
    {
        $requestedList = trim((string)$include);
        if ($requestedList === '') {
            return new self([]);
        }
        $fieldNames = [];
        foreach (explode(',', $requestedList) as $requested) {
            $requested = trim($requested);
            if (!isset(self::ORGANIZER_FIELD_BY_INCLUDE[$requested])) {
                throw new DetailedException('Cannot include ' . $requested . ' in clubs');
            }
            $fieldNames[] = self::ORGANIZER_FIELD_BY_INCLUDE[$requested];
        }
        return new self($fieldNames);
    }

    public function areEmpty(): bool
    {
        return !$this->fieldNames;
    }

    public function names(): array
    {
        return $this->fieldNames;
    }
}
