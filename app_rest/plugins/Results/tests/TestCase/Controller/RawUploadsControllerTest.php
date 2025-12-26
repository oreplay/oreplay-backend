<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Lib\Consts\UploadTypes;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Event;
use Results\Model\Entity\RawUpload;
use Results\Model\Entity\UploadLog;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\RawUploadsTable;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\RawUploadsFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\TokensFixture;
use Results\Test\Fixture\UploadLogsFixture;
use Results\Test\TestCase\Controller\UploadExamples\StartExamples;

class RawUploadsControllerTest extends ApiCommonErrorsTest
{
    protected array $fixtures = [
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        TokensFixture::LOAD,
        RawUploadsFixture::LOAD,
        UploadLogsFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/rawUploads/';
    }

    public function testAddNew()
    {
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = ['oreplay_data_transfer' => StartExamples::startImportSmall()];
        $this->post($this->_getEndpoint(), $data);

        $jsonDecoded = $this->assertJsonResponseOK();
        $decodedData = $jsonDecoded['data'];
        //$this->assertEquals(UploadTypes::START_LIST, $decodedData['upload_type']);
        //$this->assertEquals(null, $decodedData['upload_status']);
        $this->assertEquals(UploadLog::STATE_START, $decodedData['state']);
        //$this->assertEquals('', $decodedData['info']);

        /** @var RawUpload $raw */
        $raw = RawUploadsTable::load()->find()->orderDesc('created')->first();
        $this->assertEquals(
            $data['oreplay_data_transfer']['event']['description'],
            $raw->getDataAsArray()['oreplay_data_transfer']['event']['description'],
        );
    }
}
