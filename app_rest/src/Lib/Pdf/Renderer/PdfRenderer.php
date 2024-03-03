<?php

declare(strict_types = 1);

namespace App\Lib\Pdf\Renderer;

use App\Lib\Consts\CertificateOrientation;
use App\Lib\Pdf\Container\Container;
use Cake\Http\Response;

interface PdfRenderer
{
    /**
     * Set needed data to render pdf
     *
     * @param Container $pdfContents Data
     * @param string $orientation Page orientation
     * @param bool $showFoot Shows footer
     *
     * @return mixed
     */
    public function setData(Container $pdfContents, $orientation = CertificateOrientation::PORTRAIT, $showFoot = true);

    /**
     * Get default bucket to place rendered pdf in
     *
     * @return string
     */
    public function getBucket();

    /**
     * Render the pdf
     *
     * @return string
     */
    public function render();

    /**
     * Get the title of the pdf based on data set
     *
     * @return mixed
     */
    public function getTitle();

    /**
     * Set pdf headers for download
     *
     * @param string $title Pdf title (optional)
     *
     * @return void
     */
    public function setPdfHeadersForDownload(Response $response, $title = null): Response;
}
