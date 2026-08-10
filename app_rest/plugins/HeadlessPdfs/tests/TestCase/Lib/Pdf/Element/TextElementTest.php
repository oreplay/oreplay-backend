<?php

declare(strict_types = 1);

namespace HeadlessPdfs\Test\TestCase\Lib\Pdf\Element;

use App\Lib\Exception\InvalidPayloadException;
use Cake\TestSuite\TestCase;
use HeadlessPdfs\Lib\Pdf\Element\TextElement;
use HeadlessPdfs\Lib\Pdf\PageGeometry;
use HeadlessPdfs\Test\TestCase\Lib\Pdf\RecordingTcpdf;

class TextElementTest extends TestCase
{
    public function testType()
    {
        $this->assertEquals('text', TextElement::type());
    }

    public function testValidate_normalizes()
    {
        $normalized = TextElement::validate([
            'type' => 'text',
            'content' => 'Bilbao, 7 Aug 2026',
            'size' => 10,
            'position' => ['x' => 20, 'y' => 260],
        ], 'p');

        $this->assertEquals([
            'type' => 'text',
            'content' => 'Bilbao, 7 Aug 2026',
            'size' => 10.0,
            'color' => '#000000',
            'style' => 'regular',
            'x' => 20.0,
            'y' => 260.0,
        ], $normalized);
    }

    public function testValidate_missingX_throws()
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('p.position.x: expected a number between 0 and 1189');
        TextElement::validate(['type' => 'text', 'content' => 'x', 'position' => ['y' => 10]], 'p');
    }

    public function testRender_leftAlignedFromXToTheRightEdge()
    {
        $pdf = new RecordingTcpdf();
        $pdf->addPage();

        (new TextElement())->render($pdf, [
            'type' => 'text',
            'content' => 'Otra sección',
            'size' => 18.0,
            'x' => 100.0,
            'y' => 250.0,
        ], PageGeometry::a4());

        $calls = $pdf->callsTo('addTextCellXY');
        $this->assertCount(1, $calls);
        $this->assertEquals([
            'txt' => 'Otra sección',
            'posx' => 100.0,
            'posy' => 250.0,
            'width' => 110.0,
            'height' => 0.0,
            'valign' => 'T',
            'halign' => 'L',
            'drawcell' => false,
        ], $calls[0]);
    }

    public function testRender_centerY_spansFullPageHeight()
    {
        $pdf = new RecordingTcpdf();
        $pdf->addPage();

        (new TextElement())->render($pdf, [
            'type' => 'text',
            'content' => 'x',
            'size' => 12.0,
            'x' => 20.0,
            'y' => 'center',
        ], PageGeometry::a4());

        $calls = $pdf->callsTo('addTextCellXY');
        $this->assertEquals(0.0, $calls[0]['posy']);
        $this->assertEquals(297.0, $calls[0]['height']);
        $this->assertEquals('C', $calls[0]['valign']);
        $this->assertEquals('L', $calls[0]['halign']);
    }
}
