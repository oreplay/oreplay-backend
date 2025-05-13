<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Entity;

use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;
use Results\Model\Entity\Split;

class SplitTest extends TestCase
{
    private function _newSplit()
    {
        $split = new Split();
        $split->id = 'mainID';
        $split->order_number = '2';
        $split->is_intermediate = false;
        $split->reading_time = new FrozenTime('2024-01-02 10:00:05');
        $split->created = new FrozenTime('2024-01-02 10:09:10');
        return $split;
    }

    public function testShouldDisplayCurrent()
    {
        $msg = '3 keep repeated split as revisited control';
        $differentOrderNumber = $this->_newSplit();
        $differentOrderNumber->order_number = '1';
        $res = $this->_newSplit()->shouldDisplayCurrent($differentOrderNumber);
        $this->assertEquals($msg, $res->reason());
        $this->assertTrue($res->shouldDisplay(), $msg);
        //
        $msg = '4 keep if none has reading time because is DNS or MP';
        $noTime = $this->_newSplit();
        $noTime->reading_time = null;
        $res = $noTime->shouldDisplayCurrent($this->_newSplit());
        $this->assertEquals($msg, $res->reason());
        $this->assertTrue($res->shouldDisplay(), $msg);
        //
        $msg = '3 keep repeated split as revisited control';
        $revisited = $this->_newSplit();
        $res = $revisited->shouldDisplayCurrent($revisited);
        $this->assertEquals($msg, $res->reason());
        $this->assertTrue($res->shouldDisplay(), $msg);
        //
        $msg = '4 keep if none has reading time because is DNS or MP';
        $noTime = $this->_newSplit();
        $noTime->reading_time = null;
        $res = $noTime->shouldDisplayCurrent($noTime);
        $this->assertEquals($msg, $res->reason());
        $this->assertTrue($res->shouldDisplay(), $msg);
        //
        $msg = '5 keep current if both radios with different time AND rogaining when different reading_time';
        $isRadio = $this->_newSplit();
        $isRadio->is_intermediate = true;
        $lastRadio = $this->_newSplit();
        $lastRadio->is_intermediate = true;
        $lastRadio->reading_time = new FrozenTime('2024-01-02 10:00:00');
        $res = $isRadio->shouldDisplayCurrent($lastRadio);
        $this->assertEquals($msg, $res->reason());
        $this->assertTrue($res->shouldDisplay(), $msg);
        //
        $msg = '5 keep current if both radios with different time AND rogaining when different reading_time';
        $rogaining = $this->_newSplit();
        $rogaining->order_number = null;
        $rogaining->reading_time = new FrozenTime('2024-01-02 10:00:05');
        $rogaining->is_intermediate = true;
        $rogainingRadio = $this->_newSplit();
        $rogainingRadio->order_number = null;
        $rogainingRadio->reading_time = new FrozenTime('2024-01-02 10:00:00');
        $rogainingRadio->is_intermediate = true;
        $res = $rogaining->shouldDisplayCurrent($rogainingRadio);
        $this->assertEquals($msg, $res->reason());
        $this->assertTrue($res->shouldDisplay(), $msg);
        //
        $msg = '6 skip current without time if both are radio AND rogaining when any no radio exists';
        $isRadio = $this->_newSplit();
        $isRadio->is_intermediate = true;
        $res = $isRadio->shouldDisplayCurrent($isRadio);
        $this->assertEquals($msg, $res->reason());
        $this->assertFalse($res->shouldDisplay(), $msg);
        //
        $msg = '7 skip rogaining when any no radio exists';
        $rogainingCurrentRadio = $this->_newSplit();
        $rogainingCurrentRadio->order_number = null;
        $rogainingCurrentRadio->reading_time = new FrozenTime('2024-01-02 10:00:05');
        $rogainingCurrentRadio->is_intermediate = true;
        $rogainingLastNoRadio = $this->_newSplit();
        $rogainingLastNoRadio->order_number = null;
        $rogainingLastNoRadio->reading_time = new FrozenTime('2024-01-02 10:00:00');
        $rogainingLastNoRadio->is_intermediate = false;
        $res = $rogainingCurrentRadio->shouldDisplayCurrent($rogainingLastNoRadio);
        $this->assertEquals($msg, $res->reason());
        $this->assertFalse($res->shouldDisplay(), $msg);
        //
        $msg = '8 skip rogaining when radio with same time';
        $rogainingCurrentRadio = $this->_newSplit();
        $rogainingCurrentRadio->order_number = null;
        $rogainingCurrentRadio->is_intermediate = true;
        $rogainingLastNoRadio = $this->_newSplit();
        $rogainingLastNoRadio->order_number = null;
        $rogainingLastNoRadio->is_intermediate = false;
        $res = $rogainingCurrentRadio->shouldDisplayCurrent($rogainingLastNoRadio);
        $this->assertEquals($msg, $res->reason());
        $this->assertFalse($res->shouldDisplay(), $msg);
        //
        $msg = '10 do not return radios without time, this should never happen';
        $isRadio = $this->_newSplit();
        $isRadio->is_intermediate = true;
        $isRadio->reading_time = null;
        $res = $isRadio->shouldDisplayCurrent($this->_newSplit());
        $this->assertEquals($msg, $res->reason());
        $this->assertFalse($res->shouldDisplay(), $msg);
    }
}
