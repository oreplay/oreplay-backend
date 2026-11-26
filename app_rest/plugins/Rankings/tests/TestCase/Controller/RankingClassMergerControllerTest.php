<?php

declare(strict_types = 1);

namespace Rankings\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\Fixture\OauthAccessTokensFixture;
use App\Test\Fixture\UsersFixture;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Rankings\Lib\ScoringAlgorithms\SimpleScoreCalculator;
use Rankings\Model\Entity\Ranking;
use Rankings\Model\Table\RankingsTable;
use Rankings\Test\Fixture\RankingsFixture;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Event;
use Results\Model\Entity\Runner;
use Results\Model\Entity\Stage;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\RunnersTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\FederationsFixture;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\StageTypesFixture;
use Results\Test\Fixture\UsersEventsFixture;

class RankingClassMergerControllerTest extends ApiCommonErrorsTest
{
    private const RANKING_ID = 'a1b2c3d4-0000-4000-8000-0000000000c1';

    protected array $fixtures = [
        FederationsFixture::LOAD,
        UsersEventsFixture::LOAD,
        UsersFixture::LOAD,
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        StageTypesFixture::LOAD,
        ClassesFixture::LOAD,
        RunnersFixture::LOAD,
        RunnerResultsFixture::LOAD,
        RankingsFixture::LOAD,
        OauthAccessTokensFixture::LOAD,
    ];

    public function setUp(): void
    {
        parent::setUp();
        $Rankings = RankingsTable::load();
        $Rankings->deleteCache(self::RANKING_ID);
        $ranking = $Rankings->patchFromNewWithUuid([
            'id' => self::RANKING_ID,
            'scoring_algorithm' => SimpleScoreCalculator::class,
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => Stage::FIRST_STAGE,
            'max_points' => 100,
            'round_precision' => Ranking::USE_FLOOR_INSTEAD_OF_ROUND,
        ]);
        $Rankings->saveOrFail($ranking);
    }

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/rankings/' . self::RANKING_ID . '/classMerger/';
    }

    public function testMergeMovesRunnersAndDeletesTheSourceClass()
    {
        $this->skipNextRequestInSwagger();
        $this->post($this->_getEndpoint(), ['from_class' => 'ME', 'to_class' => 'FE']);

        $this->assertJsonResponseOK();
        $runner = RunnersTable::load()->get(Runner::FIRST_RUNNER);
        $this->assertEquals(ClassEntity::FE, $runner->class_id);
        $this->assertNull(ClassesTable::load()->find()->where(['id' => ClassEntity::ME])->first());
    }

    public function testMergingAClassIntoItselfIsRejectedAndDeletesNothing()
    {
        $this->skipNextRequestInSwagger();
        $this->post($this->_getEndpoint(), ['from_class' => 'ME', 'to_class' => 'ME']);

        $this->assertResponseCode(400);
        $this->assertNotNull(RunnersTable::load()->find()->where(['id' => Runner::FIRST_RUNNER])->first());
        $this->assertNotNull(ClassesTable::load()->find()->where(['id' => ClassEntity::ME])->first());
    }

    public function testMergeNeedsBothClasses()
    {
        $this->skipNextRequestInSwagger();
        $this->post($this->_getEndpoint(), ['from_class' => 'ME']);

        $this->assertResponseCode(400);
    }

    public function testGetDoesNotMerge()
    {
        $this->skipNextRequestInSwagger();
        $this->get($this->_getEndpoint() . '?from_class=ME&to_class=FE');

        $this->assertNotEquals(200, $this->_response->getStatusCode(), $this->_getBodyAsString());
        $runner = RunnersTable::load()->get(Runner::FIRST_RUNNER);
        $this->assertEquals(ClassEntity::ME, $runner->class_id);
        $this->assertNotNull(ClassesTable::load()->find()->where(['id' => ClassEntity::ME])->first());
    }
}
