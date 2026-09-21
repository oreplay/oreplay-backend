<?php

declare(strict_types = 1);

use Migrations\BaseMigration;
use Results\Lib\Consts\CountryNames;
use Results\Lib\Consts\RegionNames;

class UseCodesForCountryAndRegionInOrganizers extends BaseMigration
{
    private const COUNTRY_CODE_LENGTH = 2;
    private const REGION_CODE_LENGTH = 3;
    private const NAME_LENGTH = 50;

    public function up(): void
    {
        $this->_addCodeColumns();
        foreach ($this->fetchAll('SELECT id, country, region FROM organizers') as $organizer) {
            $this->execute('UPDATE organizers SET country_code = ?, region_code = ? WHERE id = ?', [
                self::_countryCodeOf($organizer['country']),
                self::_regionCodeOf($organizer['region']),
                $organizer['id'],
            ]);
        }
        $this->_removeNameColumns();
    }

    public function down(): void
    {
        $this->_addNameColumns();
        foreach ($this->fetchAll('SELECT id, country_code, region_code FROM organizers') as $organizer) {
            $this->execute('UPDATE organizers SET country = ?, region = ? WHERE id = ?', [
                CountryNames::NAME_OF_ISO2[$organizer['country_code']] ?? null,
                RegionNames::NAME_OF_ISO_3166_2[self::_isoRegionOf($organizer)] ?? null,
                $organizer['id'],
            ]);
        }
        $this->_removeCodeColumns();
    }

    private function _addCodeColumns(): void
    {
        $table = $this->table('organizers');
        $table
            ->addColumn('country_code', 'string', [
                'limit' => self::COUNTRY_CODE_LENGTH,
                'null' => true,
                'default' => null,
                'after' => 'name',
            ])
            ->addColumn('region_code', 'string', [
                'limit' => self::REGION_CODE_LENGTH,
                'null' => true,
                'default' => null,
                'after' => 'country_code',
            ]);
        $table->update();
    }

    private function _removeNameColumns(): void
    {
        $table = $this->table('organizers');
        $table->removeColumn('country');
        $table->removeColumn('region');
        $table->update();
    }

    private function _addNameColumns(): void
    {
        $table = $this->table('organizers');
        $table
            ->addColumn('country', 'string', [
                'limit' => self::NAME_LENGTH,
                'null' => true,
                'default' => null,
                'after' => 'name',
            ])
            ->addColumn('region', 'string', [
                'limit' => self::NAME_LENGTH,
                'null' => true,
                'default' => null,
                'after' => 'country',
            ]);
        $table->update();
    }

    private function _removeCodeColumns(): void
    {
        $table = $this->table('organizers');
        $table->removeColumn('country_code');
        $table->removeColumn('region_code');
        $table->update();
    }

    private static function _countryCodeOf(?string $countryName): ?string
    {
        $code = array_search($countryName, CountryNames::NAME_OF_ISO2, true);
        return $code === false ? null : $code;
    }

    private static function _regionCodeOf(?string $regionName): ?string
    {
        $isoRegion = array_search($regionName, RegionNames::NAME_OF_ISO_3166_2, true);
        return $isoRegion === false ? null : explode('-', (string)$isoRegion)[1];
    }

    private static function _isoRegionOf(array $organizer): string
    {
        return (string)$organizer['country_code'] . '-' . (string)$organizer['region_code'];
    }
}
