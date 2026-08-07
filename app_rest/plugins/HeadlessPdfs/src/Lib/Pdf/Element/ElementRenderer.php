<?php

declare(strict_types = 1);

namespace HeadlessPdfs\Lib\Pdf\Element;

use Com\Tecnick\Pdf\Tcpdf;

/**
 * One PDF element type, owning both its validation and its drawing.
 *
 * Keeping the two together is what stops the validator from restating a schema that
 * really belongs to the thing that draws it.
 */
interface ElementRenderer
{
    public static function type(): string;

    /**
     * @param array $el Raw element from the request.
     * @param string $path Where this element sits in the payload, for error messages.
     * @return array Normalized element, with defaults applied.
     * @throws \App\Lib\Exception\InvalidPayloadException
     */
    public static function validate(array $el, string $path): array;

    /**
     * @param array $el A normalized element, as returned by validate().
     */
    public function render(Tcpdf $pdf, array $el): void;
}
