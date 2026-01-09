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

    protected function _getEndpointWithToken($token): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . $token . '/rawUploads/';
    }

    public function testAddNew()
    {
        $this->skipNextRequestInSwagger();
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
        $raw = RawUploadsTable::load()->find()->orderByDesc('created')->first();
        $this->assertEquals(
            $data['oreplay_data_transfer']['event']['description'],
            $raw->getDataAsArray()['oreplay_data_transfer']['event']['description'],
        );
    }

    public function testGetList()
    {
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $this->get($this->_getEndpointWithToken(TokensFixture::FIRST_TOKEN));

        $jsonDecoded = $this->assertJsonResponseOK();
        $expected = [
            'data' => [
                [
                    '_c' => 'SimpleLog',
                    'link_upload' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9bBMEWb/rawUploads/2024-01-02T10%3A00%3A05%2B00%3A00',
                    'upload_type' => 'start_list',
                    'state' => 1,
                    'link_stage' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9bBMEWb/rawUploads/?stage_id=51d63e99-5d7c-4382-a541-8567015d8eed'
                ]
            ]
        ];
        $this->assertEquals($expected, $jsonDecoded);
    }

    public function testGetData()
    {
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        RawUploadsTable::load()->updateAll(['file_data' => '{}'], ['id' => RawUploadsFixture::FIRST]);
        $this->get($this->_getEndpointWithToken(TokensFixture::FIRST_TOKEN) . '2024-01-02T10%3A00%3A05%2B00%3A00');

        $jsonDecoded = $this->assertJsonResponseOK();
        $expected = [
            'data' => [
                '_c' => RawUpload::class,
                'id' => '8b299215-d9ad-4854-9173-342063f9a410',
                'event_id' => '8f3b542c-23b9-4790-a113-b83d476c0ad9',
                'stage_id' => '51d63e99-5d7c-4382-a541-8567015d8eed',
                'upload_log_id' => 'f3414e0b-e605-494d-89f0-85d0bfbab2a0',
                'file_data' => [
                ],
                'created' => '2024-01-02T10:00:05.000+00:00',
                'modified' => '2024-01-02T10:00:05.000+00:00',
                'deleted' => null,
            ]
        ];
        $this->assertEquals($expected, $jsonDecoded);
    }

    public function testDelete()
    {
        $amount = RawUploadsTable::load()->find()
            ->where(['id' => RawUploadsFixture::FIRST])
            ->withDeleted(true)
            ->count();
        $this->assertEquals(1, $amount);

        $this->delete($this->_getEndpointWithToken('') . 'old');
        $this->assertResponseOK();

        $amount = RawUploadsTable::load()->find()
            ->where(['id' => RawUploadsFixture::FIRST])
            ->withDeleted(true)
            ->count();
        $this->assertEquals(0, $amount);
    }
}
