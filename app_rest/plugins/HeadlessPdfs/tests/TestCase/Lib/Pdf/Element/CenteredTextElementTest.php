<?php

declare(strict_types = 1);

namespace HeadlessPdfs\Test\TestCase\Lib\Pdf\Element;

use App\Lib\Exception\InvalidPayloadException;
use Cake\TestSuite\TestCase;
use HeadlessPdfs\Lib\Pdf\Element\CenteredTextElement;
use HeadlessPdfs\Test\TestCase\Lib\Pdf\RecordingTcpdf;

class CenteredTextElementTest extends TestCase
{
    public function testType()
    {
        $this->assertEquals('centeredElement', CenteredTextElement::type());
    }

    public function testValidate_normalizes()
    {
        $normalized = CenteredTextElement::validate([
            'type' => 'centeredElement',
            'content' => 'Título del documento',
            'size' => 36,
            'position' => ['y' => 'center'],
        ], 'p');

        $this->assertEquals([
            'type' => 'centeredElement',
            'content' => 'Título del documento',
            'size' => 36.0,
            'y' => 'center',
        ], $normalized);
    }

    public function testValidate_appliesDefaultSize()
    {
        $normalized = CenteredTextElement::validate(
            ['type' => 'centeredElement', 'content' => 'x', 'position' => ['y' => 10]],
            'p',
        );
        $this->assertEquals(12.0, $normalized['size']);
    }

    public function testValidate_missingContent_throws()
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('p.content: is required and must be a string');
        CenteredTextElement::validate(['type' => 'centeredElement', 'position' => ['y' => 10]], 'p');
    }

    public function testValidate_missingY_throws()
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('p.position.y: expected a number between 0 and 297, or "center"');
        CenteredTextElement::validate(['type' => 'centeredElement', 'content' => 'x'], 'p');
    }

    public function testRender_numericY_spansFullWidthAtThatY()
    {
        $pdf = new RecordingTcpdf();
        $pdf->addPage();

        (new CenteredTextElement())->render($pdf, [
            'type' => 'centeredElement',
            'content' => 'Segunda página',
            'size' => 24.0,
            'y' => 150.0,
        ]);

        $calls = $pdf->callsTo('addTextCellXY');
        $this->assertCount(1, $calls);
        $this->assertEquals([
            'txt' => 'Segunda página',
            'posx' => 0.0,
            'posy' => 150.0,
            'width' => 210.0,
            'height' => 0.0,
            'valign' => 'T',
            'halign' => 'C',
            'drawcell' => false,
        ], $calls[0]);
    }

    public function testRender_centerY_spansFullPageHeightAndCentresVertically()
    {
        $pdf = new RecordingTcpdf();
        $pdf->addPage();

        (new CenteredTextElement())->render($pdf, [
            'type' => 'centeredElement',
            'content' => 'Título del documento',
            'size' => 36.0,
            'y' => 'center',
        ]);

        $calls = $pdf->callsTo('addTextCellXY');
        $this->assertEquals(0.0, $calls[0]['posy']);
        $this->assertEquals(297.0, $calls[0]['height']);
        $this->assertEquals('C', $calls[0]['valign']);
        $this->assertEquals('C', $calls[0]['halign']);
    }
}
