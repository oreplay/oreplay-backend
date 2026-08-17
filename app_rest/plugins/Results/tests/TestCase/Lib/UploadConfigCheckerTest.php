<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib;

use App\Lib\Exception\InvalidPayloadException;
use Cake\TestSuite\TestCase;
use Results\Lib\Consts\UploadTypes;
use Results\Lib\UploadConfigChecker;

class UploadConfigCheckerTest extends TestCase
{
    private const EVENT_ID = 'fake_event_id';

    private function _transfer(mixed $classes = []): array
    {
        return [
            'configuration' => ['contents' => 'ResultList', 'results_type' => 'Breakdown'],
            'event' => [
                'id' => self::EVENT_ID,
                'stages' => [['id' => 'fake_stage_id', 'classes' => $classes]],
            ],
        ];
    }

    public function testFromPayload_shouldUnwrapTheEnvelope()
    {
        $checker = UploadConfigChecker::fromPayload(['oreplay_data_transfer' => $this->_transfer()])
            ->validateStructure(self::EVENT_ID);

        $this->assertEquals('fake_stage_id', $checker->getStageId());
        $this->assertEquals(UploadTypes::SPLITS, $checker->preCheckType());
    }

    public function testFromPayload_shouldRejectAPayloadWithoutTheEnvelope()
    {
        $this->expectException(InvalidPayloadException::class);

        UploadConfigChecker::fromPayload($this->_transfer());
    }

    /**
     * The seam IOF XML needs: an XML document has no oreplay_data_transfer wrapper to unwrap, so it
     * hands over the transfer node directly.
     */
    public function testFromTransfer_shouldAcceptAnAlreadyUnwrappedTransfer()
    {
        $checker = UploadConfigChecker::fromTransfer($this->_transfer())
            ->validateStructure(self::EVENT_ID);

        $this->assertEquals('fake_stage_id', $checker->getStageId());
        $this->assertEquals(UploadTypes::SPLITS, $checker->preCheckType());
    }

    public function testOverwriteStageId_shouldReplaceTheStageIdWithoutTheEnvelope()
    {
        $checker = UploadConfigChecker::fromTransfer($this->_transfer())
            ->validateStructure(self::EVENT_ID);

        $checker->overwriteStageId('another_stage')->validateStructure(self::EVENT_ID);

        $this->assertEquals('another_stage', $checker->getStageId());
    }

    public function testGetClasses_shouldAcceptAGeneratorSoXmlCanStreamOneClassAtATime()
    {
        $classes = (function () {
            yield ['short_name' => 'E'];
            yield ['short_name' => 'F'];
        })();

        $checker = UploadConfigChecker::fromTransfer($this->_transfer($classes))
            ->validateStructure(self::EVENT_ID);

        $names = [];
        foreach ($checker->getClasses() as $class) {
            $names[] = $class['short_name'];
        }
        $this->assertEquals(['E', 'F'], $names);
    }

    public function testGetClasses_shouldStillRejectSomethingThatCannotBeIterated()
    {
        $this->expectException(InvalidPayloadException::class);

        UploadConfigChecker::fromTransfer($this->_transfer('not-a-list'))
            ->validateStructure(self::EVENT_ID);
    }
}
