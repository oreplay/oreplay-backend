<?php

declare(strict_types = 1);

namespace HeadlessPdfs\Lib\Pdf;

use App\Lib\Exception\InvalidPayloadException;
use HeadlessPdfs\Lib\Pdf\Element\BreakPageElement;
use HeadlessPdfs\Lib\Pdf\Element\ElementRegistry;

class PdfBookValidator
{
    public const DEFAULT_MAX_PAGES = 500;
    public const DEFAULT_MAX_ELEMENTS = 7000;
    public const DEFAULT_MAX_CONTENT_CHARS = 250000;
    public const DEFAULT_MAX_CONTENT_COST = 13000000;
    public const MAX_FILENAME_LENGTH = 200;
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

    public static function maxPages(): int
    {
        return self::limit('PDF_MAX_PAGES', self::DEFAULT_MAX_PAGES);
    }

    public static function maxElements(): int
    {
        return self::limit('PDF_MAX_ELEMENTS', self::DEFAULT_MAX_ELEMENTS);
    }

    /**
     * The characters of text a single request may lay out, summed over every element.
     *
     * A per-element cap alone does not bound a request: laying out text costs roughly
     * linear time per character regardless of how it is split up, so only the total is a
     * meaningful budget. This one bounds the low-size, high-volume end, where peak memory
     * tracks the character count; maxContentCost() bounds the other end.
     */
    public static function maxContentChars(): int
    {
        return self::limit('PDF_MAX_CONTENT_CHARS', self::DEFAULT_MAX_CONTENT_CHARS);
    }

    public static function maxContentCost(): int
    {
        return self::limit('PDF_MAX_CONTENT_COST', self::DEFAULT_MAX_CONTENT_COST);
    }

    /**
     * A non-positive or non-numeric override would disable the limit entirely, so it falls
     * back to the default instead: a typo in the deployment must not remove the bound.
     */
    private static function limit(string $envName, int $default): int
    {
        $value = (int)env($envName, (string)$default);
        return $value > 0 ? $value : $default;
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
        // an oversized header field-value overflows nginx's fastcgi_buffer_size and surfaces
        // as a 502, hiding the fact that the request itself was the problem
        if (mb_strlen($filename) > self::MAX_FILENAME_LENGTH) {
            throw new InvalidPayloadException(
                self::ROOT . '.filename: exceeds the maximum length of '
                . self::MAX_FILENAME_LENGTH . ' characters'
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
        $parts = is_string($img) ? parse_url($img) : null;
        $host = is_array($parts) ? ($parts['host'] ?? null) : null;
        $scheme = is_array($parts) ? ($parts['scheme'] ?? null) : null;
        $scheme = $scheme !== null ? strtolower($scheme) : null;
        if (!$host || !in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidPayloadException(self::ROOT . '.img: must be an absolute http(s) URL');
        }
        // scheme and host are case-insensitive; parse_url() does not normalize case, so both
        // sides of the comparison are lowercased here
        $host = strtolower($host);
        // checked here so a disallowed host is a 400 decided before any fetch is attempted;
        // allowedImageHosts() already lowercases its entries
        if (!in_array($host, self::allowedImageHosts(), true)) {
            throw new InvalidPayloadException(
                self::ROOT . '.img: host "' . $host . '" is not allowed'
            );
        }
        return self::normalizedUrl($parts, $scheme, $host);
    }

    /**
     * The URL rebuilt with its scheme and host lowercased, everything else byte-identical.
     *
     * The consuming file library repeats this allowlist check with case-sensitive
     * comparisons of its own, so handing it the URL as typed would turn an explicitly
     * allowed mixed-case host into a fetch failure. Path, query and fragment are
     * case-sensitive and are carried over untouched.
     */
    private static function normalizedUrl(array $parts, string $scheme, string $host): string
    {
        $credentials = '';
        if (isset($parts['user'])) {
            $credentials = $parts['user']
                . (isset($parts['pass']) ? ':' . $parts['pass'] : '') . '@';
        }
        return $scheme . '://' . $credentials . $host
            . (isset($parts['port']) ? ':' . $parts['port'] : '')
            . ($parts['path'] ?? '')
            . (isset($parts['query']) ? '?' . $parts['query'] : '')
            . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
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
        $contentChars = 0;
        $contentCost = 0;
        $maxPages = self::maxPages();
        $maxElements = self::maxElements();
        $maxContentChars = self::maxContentChars();
        $maxContentCost = self::maxContentCost();
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
                    self::assertUnder($pages, $maxPages, 'pages');
                }
                $elements[] = $normalized;
                self::assertUnder(count($elements), $maxElements, 'elements');
                // both accumulated before anything is rendered, so a payload that busts
                // either budget costs no layout time at all
                $length = mb_strlen($normalized['content'] ?? '');
                $contentChars += $length;
                self::assertUnder($contentChars, $maxContentChars, 'content characters');
                $contentCost += (int)round($length * ($normalized['size'] ?? 0));
                self::assertUnder($contentCost, $maxContentCost, 'content cost');
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
