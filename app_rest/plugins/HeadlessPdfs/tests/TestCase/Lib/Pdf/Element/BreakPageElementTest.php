<?php

declare(strict_types = 1);

namespace HeadlessPdfs\Test\TestCase\Lib\Pdf\Element;

use Cake\TestSuite\TestCase;
use HeadlessPdfs\Lib\Pdf\Element\BreakPageElement;

class BreakPageElementTest extends TestCase
{
    public function testType()
    {
        $this->assertEquals('breakPage', BreakPageElement::type());
    }

    public function testValidate_normalizesToTypeOnly()
    {
        $normalized = BreakPageElement::validate(['type' => 'breakPage', 'ignored' => 'x'], 'p');
        $this->assertEquals(['type' => 'breakPage'], $normalized);
    }
}
