<?php

declare(strict_types = 1);

namespace HeadlessPdfs\Test\TestCase\Lib\Pdf\Element;

use App\Lib\Exception\InvalidPayloadException;
use Cake\TestSuite\TestCase;
use HeadlessPdfs\Lib\Pdf\Element\ElementFields;

class ElementFieldsTest extends TestCase
{
    public function testString_returnsTheValue()
    {
        $this->assertEquals('hi', ElementFields::string(['content' => 'hi'], 'content', 'p', 10));
    }

    public function testString_missingKey_throwsWithPath()
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('p.content: is required and must be a string');
        ElementFields::string([], 'content', 'p', 10);
    }

    public function testString_notAString_throwsWithPath()
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('p.content: is required and must be a string');
        ElementFields::string(['content' => 5], 'content', 'p', 10);
    }

    public function testString_tooLong_countsCharactersNotBytes()
    {
        // 3 multi-byte characters: 6 bytes, but only 3 characters, so a limit of 3 passes
        $this->assertEquals('ñññ', ElementFields::string(['content' => 'ñññ'], 'content', 'p', 3));

        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('p.content: exceeds the maximum length of 3 characters');
        ElementFields::string(['content' => 'ññññ'], 'content', 'p', 3);
    }

    public function testSize_defaultsTo12()
    {
        $this->assertEquals(12.0, ElementFields::size([], 'p'));
    }

    public function testSize_acceptsIntAndFloat()
    {
        $this->assertEquals(36.0, ElementFields::size(['size' => 36], 'p'));
        $this->assertEquals(10.5, ElementFields::size(['size' => 10.5], 'p'));
    }

    public function testSize_outOfRange_throws()
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('p.size: expected a number between 1 and 300');
        ElementFields::size(['size' => 301], 'p');
    }

    public function testPositionY_acceptsNumber()
    {
        $this->assertEquals(250.0, ElementFields::positionY(['position' => ['y' => 250]], 'p'));
    }

    public function testPositionY_acceptsCenterKeyword()
    {
        $this->assertEquals('center', ElementFields::positionY(['position' => ['y' => 'center']], 'p'));
    }

    public function testPositionY_boundaries()
    {
        $this->assertEquals(0.0, ElementFields::positionY(['position' => ['y' => 0]], 'p'));
        $this->assertEquals(1189.0, ElementFields::positionY(['position' => ['y' => 1189]], 'p'));

        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('p.position.y: expected a number between 0 and 1189, or "center"');
        ElementFields::positionY(['position' => ['y' => 1189.5]], 'p');
    }

    public function testPositionY_missing_throws()
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('p.position.y: expected a number between 0 and 1189, or "center"');
        ElementFields::positionY(['position' => []], 'p');
    }

    public function testPositionX_acceptsNumber()
    {
        $this->assertEquals(100.0, ElementFields::positionX(['position' => ['x' => 100]], 'p'));
    }

    public function testPositionX_rejectsCenterKeyword()
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('p.position.x: expected a number between 0 and 1189');
        ElementFields::positionX(['position' => ['x' => 'center']], 'p');
    }

    public function testPositionX_rightEdgeIsExclusive()
    {
        $this->assertEquals(1188.9, ElementFields::positionX(['position' => ['x' => 1188.9]], 'p'));

        $this->expectException(InvalidPayloadException::class);
        ElementFields::positionX(['position' => ['x' => 1189]], 'p');
    }
}
