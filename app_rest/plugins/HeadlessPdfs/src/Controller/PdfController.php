<?php

declare(strict_types = 1);

namespace HeadlessPdfs\Controller;

use App\Controller\ApiController;
use HeadlessPdfs\Lib\Pdf\PdfBookRenderer;
use HeadlessPdfs\Lib\Pdf\PdfBookValidator;

class PdfController extends ApiController
{
    public function isPublicController(): bool
    {
        return false;
    }

    protected function getMandatoryParams(): array
    {
        return [];
    }

    protected function addNew($data)
    {
        $pdfBook = PdfBookValidator::validate($data['pdfBook'] ?? null);
        $this->return = new PdfBookRenderer($pdfBook);
    }
}
