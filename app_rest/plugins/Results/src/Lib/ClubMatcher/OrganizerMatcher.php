<?php

declare(strict_types = 1);

namespace Results\Lib\ClubMatcher;

use Cake\Utility\Text;
use Results\Lib\Consts\RegionNames;
use Results\Model\Entity\Organizer;

class OrganizerMatcher
{
    private const MAX_TRIMMED_TOKENS = 4;
    private const UPLOADED_PLACE_MAX_LENGTH = 16;

    private const PLACE_ALIASES = [
        'Principado de Asturias',
        'TENERIFE',
    ];

    private array $organizersBySquashedName = [];
    private array $trimmablePlaces = [];

    /**
     * @param Organizer[] $organizers
     */
    public function __construct(array $organizers)
    {
        foreach ($organizers as $organizer) {
            $squashedName = self::_squash($organizer->name);
            if ($squashedName !== '') {
                $this->organizersBySquashedName[$squashedName][$organizer->id] = $organizer;
            }
            $this->_addTrimmablePlace($organizer->province);
            $this->_addTrimmablePlace($organizer->city);
        }
        foreach (RegionNames::NAME_OF_ISO_3166_2 as $regionName) {
            $this->_addTrimmablePlace($regionName);
        }
        foreach (self::PLACE_ALIASES as $placeAlias) {
            $this->_addTrimmablePlace($placeAlias);
        }
    }

    public function match(string $clubName): ?Organizer
    {
        $found = [];
        foreach ($this->_candidatesAfterTrimmingPlaces(self::_tokenize($clubName)) as $candidate) {
            foreach ($this->organizersBySquashedName[$candidate] ?? [] as $id => $organizer) {
                $found[$id] = $organizer;
            }
        }
        return count($found) === 1 ? reset($found) : null;
    }

    private function _candidatesAfterTrimmingPlaces(array $tokens): array
    {
        $candidates = [];
        $total = count($tokens);
        $maxLeading = min(self::MAX_TRIMMED_TOKENS, $total - 1);
        for ($leading = 0; $leading <= $maxLeading; $leading++) {
            $maxTrailing = min(self::MAX_TRIMMED_TOKENS, $total - $leading - 1);
            for ($trailing = 0; $trailing <= $maxTrailing; $trailing++) {
                $head = array_slice($tokens, 0, $leading);
                $tail = array_slice($tokens, $total - $trailing, $trailing);
                if (!$this->_isTrimmablePlace($head) || !$this->_isTrimmablePlace($tail)) {
                    continue;
                }
                $candidates[] = implode('', array_slice($tokens, $leading, $total - $leading - $trailing));
            }
        }
        return $candidates;
    }

    private function _isTrimmablePlace(array $tokens): bool
    {
        return !$tokens || isset($this->trimmablePlaces[implode(' ', $tokens)]);
    }

    private function _addTrimmablePlace(?string $place): void
    {
        $place = (string)$place;
        $truncatedOnUpload = mb_substr($place, 0, self::UPLOADED_PLACE_MAX_LENGTH);
        foreach ([$place, $truncatedOnUpload] as $spelling) {
            $normalized = self::_normalize($spelling);
            if ($normalized !== '') {
                $this->trimmablePlaces[$normalized] = true;
            }
        }
    }

    private static function _tokenize(string $value): array
    {
        $normalized = self::_normalize($value);
        return $normalized === '' ? [] : explode(' ', $normalized);
    }

    private static function _squash(string $value): string
    {
        return str_replace(' ', '', self::_normalize($value));
    }

    private static function _normalize(string $value): string
    {
        $upperAscii = mb_strtoupper(Text::transliterate($value));
        return trim((string)preg_replace('/[^A-Z0-9]+/', ' ', $upperAscii));
    }
}
