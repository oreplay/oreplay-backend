<?php

declare(strict_types = 1);

namespace HeadlessPdfs\Lib\Pdf;

use App\Lib\Exception\InvalidPayloadException;
use HeadlessPdfs\Lib\Pdf\Element\BreakPageElement;
use HeadlessPdfs\Lib\Pdf\Element\ElementRegistry;

class PdfBookValidator
{
    public const MAX_PAGES = 500;
    public const MAX_ELEMENTS = 20000;
    public const DEFAULT_FILENAME = 'document.pdf';
    public const DEFAULT_LAYOUT = 'default';

    private const ROOT = 'pdfBook';
    private const ALLOWED_HOSTS_ENV = 'PDF_IMAGE_ALLOWED_HOSTS';

    /**
     * @param mixed $pdfBook The raw pdfBook value from the request body.
     * @return array{filename: string, img: ?string, elements: array<int, array>}
     */
    public static function validate($pdfBook): array
    {
        if (!is_array($pdfBook)) {
            throw new InvalidPayloadException(self::ROOT . ': is required and must be an object');
        }
        self::assertLayout($pdfBook);

        return [
            'filename' => self::filename($pdfBook),
            'img' => self::img($pdfBook),
            'elements' => self::elements($pdfBook),
        ];
    }

    /**
     * Lowercased so this list and the img() check below share one normalisation: the
     * consuming file library's own host check is case-sensitive, so a mixed-case env
     * entry would otherwise pass validation here and then be rejected at fetch time.
     *
     * @return array<int, string>
     */
    public static function allowedImageHosts(): array
    {
        $raw = (string)env(self::ALLOWED_HOSTS_ENV, '');
        // an explicit callback, not the default truthiness filter, so a literal "0" host survives
        $hosts = array_filter(array_map('trim', explode(',', $raw)), fn ($v) => $v !== '');
        return array_values(array_map('strtolower', $hosts));
    }

    private static function assertLayout(array $pdfBook): void
    {
        $layout = $pdfBook['layout'] ?? self::DEFAULT_LAYOUT;
        if ($layout !== self::DEFAULT_LAYOUT) {
            throw new InvalidPayloadException(
                self::ROOT . '.layout: only "' . self::DEFAULT_LAYOUT . '" is supported'
            );
        }
    }

    private static function filename(array $pdfBook): string
    {
        $filename = $pdfBook['filename'] ?? self::DEFAULT_FILENAME;
        if (!is_string($filename) || $filename === '') {
            throw new InvalidPayloadException(self::ROOT . '.filename: must be a non-empty string');
        }
        // it is written into a Content-Disposition header: RFC 7230 permits no C0 control
        // character except HTAB, so the full C0 range and DEL are rejected alongside quotes
        // and path separators, not just CR/LF
        if (preg_match('#[/\\\\"\x00-\x1F\x7F]#', $filename)) {
            throw new InvalidPayloadException(
                self::ROOT . '.filename: must not contain path separators, quotes or control characters'
            );
        }
        if (!str_ends_with(strtolower($filename), '.pdf')) {
            $filename .= '.pdf';
        }
        return $filename;
    }

    private static function img(array $pdfBook): ?string
    {
        $img = $pdfBook['img'] ?? null;
        if ($img === null) {
            return null;
        }
        $host = is_string($img) ? parse_url($img, PHP_URL_HOST) : null;
        $scheme = is_string($img) ? parse_url($img, PHP_URL_SCHEME) : null;
        $scheme = $scheme !== null ? strtolower($scheme) : null;
        if (!$host || !in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidPayloadException(self::ROOT . '.img: must be an absolute http(s) URL');
        }
        // hostnames are case-insensitive; parse_url() does not normalize case, so both sides
        // of the comparison are lowercased here
        $host = strtolower($host);
        // checked here so a disallowed host is a 400 decided before any fetch is attempted;
        // allowedImageHosts() already lowercases its entries
        if (!in_array($host, self::allowedImageHosts(), true)) {
            throw new InvalidPayloadException(
                self::ROOT . '.img: host "' . $host . '" is not allowed'
            );
        }
        return $img;
    }

    /**
     * Sections are grouping only: their elements are concatenated into one stream and
     * pagination is decided entirely by breakPage.
     */
    private static function elements(array $pdfBook): array
    {
        $sections = $pdfBook['sections'] ?? null;
        if (!is_array($sections) || !$sections) {
            throw new InvalidPayloadException(
                self::ROOT . '.sections: is required and must be a non-empty list'
            );
        }

        $elements = [];
        $pages = 1;
        foreach ($sections as $sectionIndex => $section) {
            $sectionPath = self::ROOT . '.sections[' . $sectionIndex . ']';
            $rawElements = $section['elements'] ?? null;
            if (!is_array($rawElements)) {
                throw new InvalidPayloadException(
                    $sectionPath . '.elements: is required and must be a list'
                );
            }
            foreach ($rawElements as $elementIndex => $element) {
                $path = $sectionPath . '.elements[' . $elementIndex . ']';
                if (!is_array($element)) {
                    throw new InvalidPayloadException($path . ': must be an object');
                }
                $class = ElementRegistry::classFor($element['type'] ?? null, $path);
                $normalized = $class::validate($element, $path);
                if ($normalized['type'] === BreakPageElement::type()) {
                    $pages++;
                    self::assertUnder($pages, self::MAX_PAGES, 'pages');
                }
                $elements[] = $normalized;
                self::assertUnder(count($elements), self::MAX_ELEMENTS, 'elements');
            }
        }
        return $elements;
    }

    private static function assertUnder(int $actual, int $max, string $what): void
    {
        if ($actual > $max) {
            throw new InvalidPayloadException(
                self::ROOT . ': exceeds the maximum of ' . $max . ' ' . $what
            );
        }
    }
}
