<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use Results\Model\Entity\Event;
use Results\Model\Entity\Runner;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\RunnersTable;
use Results\Test\Fixture\StagesFixture;

trait NcUploadTrait
{
    private function _ncReuploadAsset(string $name): string
    {
        return dirname(__DIR__, 2) . '/assets/nc-reupload/' . $name;
    }

    private function _ncReuploadJson(string $name): array
    {
        $json = str_replace(['<EVENT_ID>', '<STAGE_ID>'], [Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2],
            file_get_contents($this->_ncReuploadAsset($name)));
        return json_decode($json, true);
    }

    private function _storedRunnerNamed(string $firstName, string $lastName): Runner
    {
        /** @var Runner $runner */
        $runner = RunnersTable::load()->find()
            ->where([
                'stage_id' => StagesFixture::STAGE_FEDO_2,
                'first_name' => $firstName,
                'last_name' => $lastName,
            ])
            ->firstOrFail();
        return $runner;
    }

    private function _assertNcEverywhere(string $firstName, string $lastName, bool $expected): void
    {
        $runner = $this->_storedRunnerNamed($firstName, $lastName);
        $this->assertSame($expected, (bool)$runner->is_nc, "runners.is_nc of $firstName");

        $resultFlags = RunnerResultsTable::load()->find()
            ->where(['runner_id' => $runner->id])
            ->all()->extract('is_nc')->toList();
        $this->assertNotEmpty($resultFlags);
        foreach ($resultFlags as $isNc) {
            $this->assertSame($expected, (bool)$isNc, "runner_results.is_nc of $firstName");
        }

        $this->assertSame($expected, (bool)$this->_stageShownByTheApi($runner)['is_nc'],
            "stage.is_nc of $firstName in the public results");
    }

    private function _stageShownByTheApi(Runner $runner): array
    {
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->skipNextRequestInSwagger();
        $this->get(ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/stages/'
            . StagesFixture::STAGE_FEDO_2 . '/results/?class_id=' . $runner->class_id);
        $body = $this->assertJsonResponseOK();
        foreach ($body['data'] as $participant) {
            if (($participant['id'] ?? null) === $runner->id) {
                return $participant['stage'];
            }
        }
        $this->fail('Runner ' . $runner->id . ' not found in the public results');
    }
}
