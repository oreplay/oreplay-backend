<?php

declare(strict_types = 1);

namespace Rankings\Lib\Summarization\Settings;

use Cake\Http\Exception\InternalErrorException;
use Rankings\Lib\ScoringAlgorithms\ScoringAlgorithm;
use function Cake\I18n\__;

class AgeGroupDefinitions
{
    private ScoringAlgorithm $_calculator;

    public function __construct(ScoringAlgorithm $calculator)
    {
        $this->_calculator = $calculator;
    }

    public function getAmountOfStages(): int
    {
        return $this->_calculator->getTotalRaces();
    }

    public function round(float|int $number): float
    {
        return $this->_calculator->round($number);
    }

    public static function getAgeSettings(string $shortName): AgeSettings
    {
        $definitions = self::getDefinitions();
        foreach ($definitions as $ageGroup => $definition) {
            if (isset($definition['classes'][$shortName])) {
                return new AgeSettings($ageGroup, $definition['classes'][$shortName], $definition['amount']);
            }
        }
        throw new InternalErrorException('Age group not found for class: ' . $shortName);
    }

    public function getAllAgeGroups(): array
    {
        $definitions = self::getDefinitions();
        return array_keys($definitions);
    }

    public static function getDefinitions(): array
    {
        return [
            'previous_absolute' => [
                'amount' => 5,
                'classes' => [
                    'M-12' => 1,
                    'F-12' => 1,
                    'M-14' => 1,
                    'F-14' => 1,
                    'M-15' => 1, // mtbo
                    'F-15' => 1, // mtbo
                    'M-16' => 1,
                    'F-16' => 1,
                    'M-17' => 1, // mtbo
                    'F-17' => 1, // mtbo
                    'M-18' => 1,
                    'F-18' => 1,
                    'M-20' => 1,
                    'F-20' => 1,
                ],
            ],
            'absolute' => [
                'amount' => 5,
                'classes' => [
                    'E' => 1,
                    'F-E' => 1,
                    'Senior' => 1,
                    'F-Senior' => 1,
                    'M-21' => 0.8,
                    'F-21' => 0.8,
                    'E-BIKE' => 0, // mtbo
                ],
            ],
            'veteran' => [
                'amount' => 5,
                'classes' => [
                    'M-35' => 1,
                    'F-35' => 1,
                    'M-40' => 1,
                    'F-40' => 1,
                    'M-45' => 1,
                    'F-45' => 1,
                    'M-50' => 1,
                    'F-50' => 1,
                    'M-55' => 1,
                    'F-55' => 1,
                    'M-60' => 1,
                    'F-60' => 1,
                    'M-65' => 1,
                    'F-65' => 1,
                    'M-70' => 1,
                    'F-70' => 1,
                ],
            ]
        ];
    }

    public function groupTranslation(mixed $keyType)
    {
        $translations = [
            'previous_absolute' => __('Jóv.'),
            'absolute' => __('Abs.'),
            'veteran' => __('Vet.'),
        ];
        return $translations[$keyType] ?? $keyType;
    }
}
