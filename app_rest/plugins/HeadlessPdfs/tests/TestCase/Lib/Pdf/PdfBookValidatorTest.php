<?php

declare(strict_types = 1);

namespace HeadlessPdfs\Test\TestCase\Lib\Pdf;

use App\Lib\Exception\InvalidPayloadException;
use Cake\TestSuite\TestCase;
use HeadlessPdfs\Lib\Pdf\Element\CenteredTextElement;
use HeadlessPdfs\Lib\Pdf\Element\ElementFields;
use HeadlessPdfs\Lib\Pdf\PdfBookValidator;

class PdfBookValidatorTest extends TestCase
{
    private function minimalBook(array $overrides = []): array
    {
        return $overrides + [
            'sections' => [
                ['elements' => [
                    ['type' => 'centeredElement', 'content' => 'x', 'position' => ['y' => 10]],
                ]],
            ],
        ];
    }

    public function testValidate_flattensSectionsIntoOneStream()
    {
        $book = [
            'layout' => 'default',
            'sections' => [
                ['elements' => [
                    ['type' => 'centeredElement', 'content' => 'Título', 'size' => 36,
                     'position' => ['y' => 'center']],
                    ['type' => 'breakPage'],
                    ['type' => 'centeredElement', 'content' => 'Segunda', 'size' => 24,
                     'position' => ['y' => 150]],
                ]],
                ['elements' => [
                    ['type' => 'text', 'content' => 'Otra', 'size' => 18,
                     'position' => ['x' => 100, 'y' => 250]],
                ]],
            ],
        ];

        $result = PdfBookValidator::validate($book);

        $this->assertCount(4, $result['elements'], 'sections are grouping only, not pages');
        $this->assertEquals(
            ['centeredElement', 'breakPage', 'centeredElement', 'text'],
            array_column($result['elements'], 'type'),
        );
    }

    public function testValidate_defaults()
    {
        $result = PdfBookValidator::validate($this->minimalBook());
        $this->assertEquals('document.pdf', $result['filename']);
        $this->assertNull($result['img']);
    }

    public function testValidate_notAnArray_throws()
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('pdfBook: is required and must be an object');
        PdfBookValidator::validate(null);
    }

    public function testValidate_missingSections_throws()
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('pdfBook.sections: is required and must be a non-empty list');
        PdfBookValidator::validate(['layout' => 'default']);
    }

    public function testValidate_emptySections_throws()
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('pdfBook.sections: is required and must be a non-empty list');
        PdfBookValidator::validate(['sections' => []]);
    }

    public function testValidate_sectionWithoutElements_throws()
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('pdfBook.sections[0].elements: is required and must be a list');
        PdfBookValidator::validate(['sections' => [['x' => 'y']]]);
    }

    public function testValidate_unknownLayout_isNotImplementedRatherThanABadRequest()
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionCode(501);
        $this->expectExceptionMessage('pdfBook.layout: only "default" is supported');
        PdfBookValidator::validate($this->minimalBook(['layout' => 'landscape']));
    }

    public function testValidate_unknownElementType_throwsWithFullPath()
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage(
            'pdfBook.sections[1].elements[0].type: unknown element type "heading"'
        );
        PdfBookValidator::validate([
            'sections' => [
                ['elements' => [['type' => 'breakPage']]],
                ['elements' => [['type' => 'heading', 'content' => 'x']]],
            ],
        ]);
    }

    public function testValidate_elementErrorCarriesItsFullPath()
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage(
            'pdfBook.sections[0].elements[1].position.x: expected a number between 0 and 1189'
        );
        PdfBookValidator::validate([
            'sections' => [
                ['elements' => [
                    ['type' => 'breakPage'],
                    ['type' => 'text', 'content' => 'x', 'position' => ['x' => 4000, 'y' => 10]],
                ]],
            ],
        ]);
    }

    public function testValidate_filenameIsSanitized()
    {
        $result = PdfBookValidator::validate($this->minimalBook(['filename' => 'certificates.pdf']));
        $this->assertEquals('certificates.pdf', $result['filename']);
    }

    public function testValidate_filenameGetsPdfSuffix()
    {
        $result = PdfBookValidator::validate($this->minimalBook(['filename' => 'certificates']));
        $this->assertEquals('certificates.pdf', $result['filename']);
    }

    public function testValidate_filenameWithPathSeparators_throws()
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage(
            'pdfBook.filename: must not contain path separators, quotes or control characters'
        );
        PdfBookValidator::validate($this->minimalBook(['filename' => '../../etc/passwd']));
    }

    public function testValidate_filenameWithCrlf_throws()
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage(
            'pdfBook.filename: must not contain path separators, quotes or control characters'
        );
        PdfBookValidator::validate($this->minimalBook(['filename' => "a\r\nX-Injected: 1"]));
    }

    public function testValidate_filenameWithControlCharacter_throws()
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage(
            'pdfBook.filename: must not contain path separators, quotes or control characters'
        );
        PdfBookValidator::validate($this->minimalBook(['filename' => "a\x00b"]));
    }

    public function testValidate_tooManyPages_throws()
    {
        $elements = array_fill(0, PdfBookValidator::DEFAULT_MAX_PAGES, ['type' => 'breakPage']);
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('pdfBook: exceeds the maximum of 500 pages');
        PdfBookValidator::validate(['sections' => [['elements' => $elements]]]);
    }

    public function testValidate_maximumPagesIsAllowed()
    {
        $elements = array_fill(0, PdfBookValidator::DEFAULT_MAX_PAGES - 1, ['type' => 'breakPage']);
        $result = PdfBookValidator::validate(['sections' => [['elements' => $elements]]]);
        $this->assertCount(PdfBookValidator::DEFAULT_MAX_PAGES - 1, $result['elements']);
    }

    public function testValidate_tooManyElements_throws()
    {
        $element = ['type' => 'centeredElement', 'content' => 'x', 'position' => ['y' => 10]];
        $elements = array_fill(0, PdfBookValidator::DEFAULT_MAX_ELEMENTS + 1, $element);
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('pdfBook: exceeds the maximum of 7000 elements');
        PdfBookValidator::validate(['sections' => [['elements' => $elements]]]);
    }

    public function testValidate_maximumElementsIsAllowed()
    {
        $element = ['type' => 'centeredElement', 'content' => 'x', 'position' => ['y' => 10]];
        $elements = array_fill(0, PdfBookValidator::DEFAULT_MAX_ELEMENTS, $element);
        $result = PdfBookValidator::validate(['sections' => [['elements' => $elements]]]);
        $this->assertCount(PdfBookValidator::DEFAULT_MAX_ELEMENTS, $result['elements']);
    }

    /**
     * @return array<int, array> elements whose contents sum to exactly $totalChars
     */
    private function elementsTotalling(int $totalChars): array
    {
        $perElement = CenteredTextElement::MAX_CONTENT_LENGTH;
        $elements = array_fill(0, intdiv($totalChars, $perElement), [
            'type' => 'centeredElement',
            'content' => str_repeat('x', $perElement),
            'position' => ['y' => 10],
        ]);
        $remainder = $totalChars % $perElement;
        if ($remainder > 0) {
            $elements[] = ['type' => 'centeredElement', 'content' => str_repeat('x', $remainder),
                           'position' => ['y' => 10]];
        }
        return $elements;
    }

    public function testValidate_maximumContentCharsIsAllowed()
    {
        $elements = $this->elementsTotalling(PdfBookValidator::DEFAULT_MAX_CONTENT_CHARS);
        $result = PdfBookValidator::validate(['sections' => [['elements' => $elements]]]);
        $this->assertCount(count($elements), $result['elements']);
    }

    public function testValidate_tooManyContentChars_throws()
    {
        $elements = $this->elementsTotalling(PdfBookValidator::DEFAULT_MAX_CONTENT_CHARS + 1);
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('pdfBook: exceeds the maximum of 250000 content characters');
        PdfBookValidator::validate(['sections' => [['elements' => $elements]]]);
    }

    /**
     * The per-element cap is not a budget: without an aggregate one, elements each well
     * inside MAX_CONTENT_LENGTH still add up to an unbounded amount of layout work.
     */
    public function testValidate_contentCharsAreSummedAcrossSections()
    {
        putenv('PDF_MAX_CONTENT_CHARS=10');
        try {
            $element = ['type' => 'centeredElement', 'content' => 'xxxxxx', 'position' => ['y' => 10]];
            $this->expectException(InvalidPayloadException::class);
            $this->expectExceptionMessage('pdfBook: exceeds the maximum of 10 content characters');
            PdfBookValidator::validate([
                'sections' => [
                    ['elements' => [$element]],
                    ['elements' => [$element]],
                ],
            ]);
        } finally {
            putenv('PDF_MAX_CONTENT_CHARS');
        }
    }

    private const COST_TEST_SIZE = 260;

    private function elementsCosting(int $cost): array
    {
        $chars = intdiv($cost, self::COST_TEST_SIZE);
        $elements = [];
        $left = $chars;
        while ($left > 0) {
            $len = min($left, CenteredTextElement::MAX_CONTENT_LENGTH);
            $left -= $len;
            $elements[] = ['type' => 'centeredElement', 'content' => str_repeat('x', $len),
                           'size' => self::COST_TEST_SIZE, 'position' => ['y' => 10]];
        }
        return $elements;
    }

    public function testValidate_maximumContentCostIsAllowed()
    {
        $elements = $this->elementsCosting(PdfBookValidator::DEFAULT_MAX_CONTENT_COST);
        $result = PdfBookValidator::validate(['sections' => [['elements' => $elements]]]);
        $this->assertCount(count($elements), $result['elements']);
    }

    public function testValidate_tooMuchContentCost_throws()
    {
        $elements = $this->elementsCosting(PdfBookValidator::DEFAULT_MAX_CONTENT_COST);
        // one more character at the same size steps exactly one size unit past the cap
        $elements[] = ['type' => 'centeredElement', 'content' => 'x',
                       'size' => self::COST_TEST_SIZE, 'position' => ['y' => 10]];
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('pdfBook: exceeds the maximum of 13000000 content cost');
        PdfBookValidator::validate(['sections' => [['elements' => $elements]]]);
    }

    public function testValidate_maximumSizeAtFullCharacterBudget_isRejected()
    {
        $elements = array_fill(0, 25, [
            'type' => 'centeredElement',
            'content' => str_repeat('x', CenteredTextElement::MAX_CONTENT_LENGTH),
            'size' => ElementFields::MAX_SIZE,
            'position' => ['y' => 10],
        ]);
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('pdfBook: exceeds the maximum of 13000000 content cost');
        PdfBookValidator::validate(['sections' => [['elements' => $elements]]]);
    }

    public function testValidate_theSameCharactersPassOrFailDependingOnSize()
    {
        $book = fn (int $size) => ['sections' => [['elements' => [[
            'type' => 'centeredElement',
            'content' => str_repeat('x', CenteredTextElement::MAX_CONTENT_LENGTH),
            'size' => $size,
            'position' => ['y' => 10],
        ]]]]];

        $result = PdfBookValidator::validate($book(12));
        $this->assertCount(1, $result['elements'], '10 000 characters at size 12 is cheap');

        putenv('PDF_MAX_CONTENT_COST=100000');
        try {
            $this->expectException(InvalidPayloadException::class);
            $this->expectExceptionMessage('pdfBook: exceeds the maximum of 100000 content cost');
            PdfBookValidator::validate($book(ElementFields::MAX_SIZE));
        } finally {
            putenv('PDF_MAX_CONTENT_COST');
        }
    }

    public function testLimits_areReadFromTheEnvironment()
    {
        putenv('PDF_MAX_PAGES=7');
        putenv('PDF_MAX_ELEMENTS=8');
        putenv('PDF_MAX_CONTENT_CHARS=9');
        putenv('PDF_MAX_CONTENT_COST=11');
        try {
            $this->assertEquals(7, PdfBookValidator::maxPages());
            $this->assertEquals(8, PdfBookValidator::maxElements());
            $this->assertEquals(9, PdfBookValidator::maxContentChars());
            $this->assertEquals(11, PdfBookValidator::maxContentCost());
        } finally {
            putenv('PDF_MAX_PAGES');
            putenv('PDF_MAX_ELEMENTS');
            putenv('PDF_MAX_CONTENT_CHARS');
            putenv('PDF_MAX_CONTENT_COST');
        }
    }

    public function testLimits_unsetOrUnusable_fallBackToTheDefaults()
    {
        putenv('PDF_MAX_ELEMENTS=0');
        putenv('PDF_MAX_CONTENT_CHARS=nonsense');
        putenv('PDF_MAX_CONTENT_COST=-1');
        try {
            $this->assertEquals(PdfBookValidator::DEFAULT_MAX_PAGES, PdfBookValidator::maxPages());
            $this->assertEquals(
                PdfBookValidator::DEFAULT_MAX_ELEMENTS,
                PdfBookValidator::maxElements(),
                'a limit of 0 would disable the bound entirely',
            );
            $this->assertEquals(
                PdfBookValidator::DEFAULT_MAX_CONTENT_CHARS,
                PdfBookValidator::maxContentChars(),
            );
            $this->assertEquals(
                PdfBookValidator::DEFAULT_MAX_CONTENT_COST,
                PdfBookValidator::maxContentCost(),
            );
        } finally {
            putenv('PDF_MAX_ELEMENTS');
            putenv('PDF_MAX_CONTENT_CHARS');
            putenv('PDF_MAX_CONTENT_COST');
        }
    }

    public function testValidate_filenameTooLong_throws()
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('pdfBook.filename: exceeds the maximum length of 200 characters');
        PdfBookValidator::validate($this->minimalBook([
            'filename' => str_repeat('a', PdfBookValidator::MAX_FILENAME_LENGTH + 1),
        ]));
    }

    public function testValidate_maximumFilenameLengthIsAllowed()
    {
        $filename = str_repeat('a', PdfBookValidator::MAX_FILENAME_LENGTH);
        $result = PdfBookValidator::validate($this->minimalBook(['filename' => $filename]));
        $this->assertEquals($filename . '.pdf', $result['filename']);
    }

    public function testValidate_imgNotAnAbsoluteHttpUrl_throws()
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('pdfBook.img: must be an absolute http(s) URL');
        PdfBookValidator::validate($this->minimalBook(['img' => '/images/background.png']));
    }

    public function testValidate_imgHostNotAllowed_throws()
    {
        putenv('PDF_BACKGROUND_ALLOWED_HOSTS=other.example.com');
        try {
            $this->expectException(InvalidPayloadException::class);
            $this->expectExceptionMessage('pdfBook.img: host "cdn.example.com" is not allowed');
            PdfBookValidator::validate($this->minimalBook(['img' => 'https://cdn.example.com/bg.png']));
        } finally {
            putenv('PDF_BACKGROUND_ALLOWED_HOSTS');
        }
    }

    public function testValidate_imgHostAllowed_passesThrough()
    {
        putenv('PDF_BACKGROUND_ALLOWED_HOSTS=cdn.example.com,other.example.com');
        try {
            $result = PdfBookValidator::validate(
                $this->minimalBook(['img' => 'https://cdn.example.com/bg.png'])
            );
            $this->assertEquals('https://cdn.example.com/bg.png', $result['img']);
        } finally {
            putenv('PDF_BACKGROUND_ALLOWED_HOSTS');
        }
    }

    /**
     * Returning the URL as typed would hand a mixed-case host straight to the file
     * library's own case-sensitive allowlist check, turning an explicitly allowed host
     * into a fetch failure reported as a 502.
     */
    public function testValidate_imgHostAllowed_isReturnedLowercased()
    {
        putenv('PDF_BACKGROUND_ALLOWED_HOSTS=cdn.example.com');
        try {
            $result = PdfBookValidator::validate(
                $this->minimalBook(['img' => 'HTTPS://CDN.Example.com/bg.png'])
            );
            $this->assertEquals('https://cdn.example.com/bg.png', $result['img']);
        } finally {
            putenv('PDF_BACKGROUND_ALLOWED_HOSTS');
        }
    }

    public function testValidate_imgPathQueryAndFragmentKeepTheirCase()
    {
        putenv('PDF_BACKGROUND_ALLOWED_HOSTS=cdn.example.com');
        try {
            $url = 'https://CDN.example.com:8443/Images/BG-Logo.PNG?Token=AbC#Frag';
            $result = PdfBookValidator::validate($this->minimalBook(['img' => $url]));
            $this->assertEquals(
                'https://cdn.example.com:8443/Images/BG-Logo.PNG?Token=AbC#Frag',
                $result['img'],
                'only the host is case-insensitive; everything after it must survive intact',
            );
        } finally {
            putenv('PDF_BACKGROUND_ALLOWED_HOSTS');
        }
    }

    public function testAllowedHosts_readsTheEnvVar()
    {
        putenv('PDF_BACKGROUND_ALLOWED_HOSTS=a.example.com, b.example.com ,');
        try {
            $this->assertEquals(
                ['a.example.com', 'b.example.com'],
                PdfBookValidator::allowedBackgroundHosts(),
                'entries are trimmed and blanks dropped',
            );
        } finally {
            putenv('PDF_BACKGROUND_ALLOWED_HOSTS');
        }
    }

    public function testAllowedHosts_unset_allowsAnyHost()
    {
        $this->assertEquals(['*'], PdfBookValidator::allowedBackgroundHosts());
    }
}
