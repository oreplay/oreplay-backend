<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\Fixture\OauthAccessTokensFixture;
use App\Test\Fixture\UsersFixture;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Model\Entity\Event;
use Results\Model\Entity\Runner;
use Results\Model\Entity\Stage;
use Results\Model\Table\RunnersTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ClubsFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\FederationsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\StageTypesFixture;
use Results\Test\Fixture\TeamsFixture;

class RunnerSearchControllerTest extends ApiCommonErrorsTest
{
    protected array $fixtures = [
        FederationsFixture::LOAD,
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        StageTypesFixture::LOAD,
        ClassesFixture::LOAD,
        ClubsFixture::LOAD,
        TeamsFixture::LOAD,
        RunnersFixture::LOAD,
        UsersFixture::LOAD,
        OauthAccessTokensFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/runners/search/';
    }

    private function _search(array $query): string
    {
        return $this->_getEndpoint() . '?' . http_build_query($query);
    }

    public function testSearchByFirstName()
    {
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $this->get($this->_search(['text' => 'Firs']));

        $json = $this->assertJsonResponseOK();
        $this->assertCount(1, $json['data']);
        $row = $json['data'][0];
        $this->assertEquals(Runner::FIRST_RUNNER, $row['id']);
        $this->assertEquals('First', $row['first_name']);
        $this->assertEquals('Runner', $row['last_name']);
        $this->assertEquals(['id', 'first_name', 'last_name', '_c'], array_keys($row));
        $this->assertEquals('RunnerSearch', $row['_c']);
    }

    public function testSearchByLastName()
    {
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $this->get($this->_search(['text' => 'Raider']));

        $json = $this->assertJsonResponseOK();
        $this->assertCount(1, $json['data']);
        $this->assertEquals(RunnersFixture::RUNNER_RAID_ID, $json['data'][0]['id']);
    }

    public function testEventIdNarrowsResults()
    {
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        // text 'R' matches both fixture runners (first_name 'First', last_name 'Raider')
        $this->get($this->_search(['text' => 'R', 'event_id' => Event::FIRST_EVENT]));

        $json = $this->assertJsonResponseOK();
        $this->assertCount(1, $json['data']);
        $this->assertEquals(Runner::FIRST_RUNNER, $json['data'][0]['id']);
    }

    public function testStageIdNarrowsResults()
    {
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $this->get($this->_search(['text' => 'R', 'stage_id' => StagesFixture::STAGE_RAID]));

        $json = $this->assertJsonResponseOK();
        $this->assertCount(1, $json['data']);
        $this->assertEquals(RunnersFixture::RUNNER_RAID_ID, $json['data'][0]['id']);
    }

    public function testExcludesRunnersOlderThanOneYear()
    {
        $this->skipNextRequestInSwagger();
        $this->_seedRunner('Ancient', 'Runner', Event::FIRST_EVENT, Stage::FIRST_STAGE, '2019-01-01 10:00:00');
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $this->get($this->_search(['text' => 'Ancient']));

        $json = $this->assertJsonResponseOK();
        $this->assertCount(0, $json['data']);
    }

    public function testCapsAtTwentyResults()
    {
        for ($i = 0; $i < 21; $i++) {
            $this->_seedRunner('Zsearch' . $i, 'Bulk', Event::FIRST_EVENT, Stage::FIRST_STAGE);
        }
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $this->get($this->_search(['text' => 'Zsearch']));

        $json = $this->assertJsonResponseOK();
        $this->assertCount(20, $json['data']);
    }

    public function testEmptyTextIsRejected()
    {
        $this->skipNextRequestInSwagger();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $this->get($this->_search(['text' => '']));

        $this->assertResponseCode(400);
    }

    public function testForbiddenForNonManager()
    {
        $this->skipNextRequestInSwagger();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_NON_ADMIN_PROVIDER);
        $this->get($this->_search(['text' => 'Firs']));

        $this->assertResponseCode(403);
    }

    private function _seedRunner(
        string $first,
        string $last,
        string $eventId,
        string $stageId,
        ?string $created = null
    ): string {
        $Runners = RunnersTable::load();
        $runner = $Runners->fillNewWithStage([], $eventId, $stageId);
        $runner->first_name = $first;
        $runner->last_name = $last;
        $Runners->saveOrFail($runner);
        if ($created) {
            $Runners->updateAll(['created' => $created], ['id' => $runner->id]);
        }
        return $runner->id;
    }
}
