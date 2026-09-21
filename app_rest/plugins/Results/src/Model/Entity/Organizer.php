<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Results\Lib\Consts\RegionNames;

/**
 * @property string $description
 */
class Organizer extends AppEntity
{
    public const ID = '8f3b542c-23b9-4790-a113-b83d476c0ad9';
    public const NAME = 'ADCON';

    protected array $_accessible = [
        '*' => false,
        'name' => true,
        'country_code' => true,
        'region_code' => true
    ];

    protected array $_virtual = [
        'region',
    ];

    protected array $_hidden = [
        'external_id',
        'created',
        'modified',
        'deleted',
    ];

    protected function _getRegion(): ?string
    {
        return RegionNames::NAME_OF_ISO_3166_2[$this->_isoRegion()] ?? null;
    }

    private function _isoRegion(): string
    {
        return (string)$this->country_code . '-' . (string)$this->region_code;
    }
}
