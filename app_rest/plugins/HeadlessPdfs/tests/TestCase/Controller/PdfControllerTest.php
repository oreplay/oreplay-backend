<?php

declare(strict_types = 1);

namespace HeadlessPdfs\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\Fixture\OauthAccessTokensFixture;
use App\Test\Fixture\OauthClientsFixture;
use App\Test\Fixture\UsersFixture;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;

class PdfControllerTest extends ApiCommonErrorsTest
{
    protected array $fixtures = [
        OauthClientsFixture::LOAD,
        OauthAccessTokensFixture::LOAD,
        UsersFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/pdf/';
    }

    private function exampleBook(): array
    {
        return ['_c' => 'PdfRequest', 'pdfBook' => [
            '_c' => 'PdfBook',
            'layout' => 'default',
            'filename' => 'document.pdf',
            'sections' => [
                ['_c' => 'PdfSection', 'elements' => [
                    ['_c' => 'PdfElement', 'type' => 'text', 'content' => 'Otra sección',
                     'size' => 18, 'position' => ['_c' => 'PdfPosition', 'x' => 100, 'y' => 250]],
                ]],
                ['_c' => 'PdfSection', 'elements' => [
                    ['_c' => 'PdfElement', 'type' => 'centeredElement',
                     'content' => 'Título del documento',
                     'size' => 36, 'position' => ['_c' => 'PdfPosition', 'y' => 'center']],
                    ['_c' => 'PdfElement', 'type' => 'breakPage'],
                    ['_c' => 'PdfElement', 'type' => 'centeredElement', 'content' => 'Segunda página',
                     'size' => 24, 'position' => ['_c' => 'PdfPosition', 'y' => 150]],
                ]],
            ],
        ]];
    }

    public function testPost_runDry_echoesTheValidatedBookAsJsonInsteadOfRenderingIt()
    {
        $book = $this->exampleBook();
        $book['runDry'] = true;
        $this->configRequest(['post' => $book]);
        $this->post(ApiController::ROUTE_PREFIX . '/pdf', $book);

        $this->assertResponseOk($this->_getBodyAsString());
        $this->assertEquals(
            $this->exampleBook()['pdfBook'],
            json_decode($this->_getBodyAsString(), true)['data'],
            'the response must carry the same shape the request posted',
        );
    }

    private function postBook(array $book): void
    {
        $this->configRequest(['headers' => [
            'Accept' => 'application/json',
            'Authorization' => $this->_request['headers']['Authorization'],
            'Content-Type' => 'application/json',
        ]]);
        $this->post(ApiController::ROUTE_PREFIX . '/pdf', json_encode($book));
    }

    public function testPost_returnsADownloadablePdf()
    {
        $this->skipNextRequestInSwagger();
        $this->postBook($this->exampleBook());

        $this->assertResponseOk($this->_getBodyAsString());
        $this->assertEquals('application/pdf', $this->_response->getHeaderLine('Content-Type'));
        $this->assertEquals(
            'attachment; filename="document.pdf"; filename*=UTF-8\'\'document.pdf',
            $this->_response->getHeaderLine('Content-Disposition'),
        );
        $this->assertStringStartsWith('%PDF-', $this->_getBodyAsString());
    }

    public function testPost_usesTheRequestedFilename()
    {
        $this->skipNextRequestInSwagger();
        $book = $this->exampleBook();
        $book['pdfBook']['filename'] = 'certificates';
        $this->postBook($book);

        $this->assertEquals(
            'attachment; filename="certificates.pdf"; filename*=UTF-8\'\'certificates.pdf',
            $this->_response->getHeaderLine('Content-Disposition'),
        );
    }

    public function testPost_exposesContentDispositionSoACrossOriginCallerCanReadTheFilename()
    {
        $this->skipNextRequestInSwagger();
        $this->postBook($this->exampleBook());

        $this->assertEquals(
            'Content-Disposition',
            $this->_response->getHeaderLine('Access-Control-Expose-Headers'),
        );
    }

    public function testPost_keepsTheCorsAllowOriginHeaderAddedBeforeTheDownloadHeaders()
    {
        $this->skipNextRequestInSwagger();
        $this->postBook($this->exampleBook());

        $this->assertEquals(
            'http://dev.example.com',
            $this->_response->getHeaderLine('Access-Control-Allow-Origin'),
            'exposing a header is useless if the response is no longer a CORS response',
        );
    }

    public function testPost_nonAsciiFilename_isRfc6266EncodedInTheHeader()
    {
        $this->skipNextRequestInSwagger();
        $book = $this->exampleBook();
        $book['pdfBook']['filename'] = 'Diplomas Vuelta a España';
        $this->postBook($book);

        $header = $this->_response->getHeaderLine('Content-Disposition');
        $this->assertStringContainsString('filename="Diplomas Vuelta a Espa__a.pdf"', $header);
        $this->assertStringContainsString(
            "filename*=UTF-8''Diplomas%20Vuelta%20a%20Espa%C3%B1a.pdf",
            $header,
        );
    }

    public function testPost_overlongFilename_is400()
    {
        $this->skipNextRequestInSwagger();
        $book = $this->exampleBook();
        // an unbounded filename would become an unbounded response header, which nginx
        // rejects as "upstream sent too big header" - a 502 for what is a bad request
        $book['pdfBook']['filename'] = str_repeat('a', 201);
        $this->postBook($book);

        $this->assertResponseError($this->_getBodyAsString());
        $this->assertEquals(
            'pdfBook.filename: exceeds the maximum length of 200 characters',
            json_decode($this->_getBodyAsString(), true)['message'],
        );
    }

    public function testPost_unknownElementType_is400()
    {
        $this->skipNextRequestInSwagger();
        $this->postBook(['pdfBook' => [
            'sections' => [['elements' => [['type' => 'heading', 'content' => 'x']]]],
        ]]);

        $this->assertResponseError($this->_getBodyAsString());
        $this->assertEquals(
            'pdfBook.sections[0].elements[0].type: unknown element type "heading"',
            json_decode($this->_getBodyAsString(), true)['message'],
        );
    }

    public function testPost_missingPdfBookKey_is400()
    {
        $this->skipNextRequestInSwagger();
        $this->postBook(['somethingElse' => 1]);

        $this->assertResponseError($this->_getBodyAsString());
        $this->assertEquals(
            'pdfBook: is required and must be an object',
            json_decode($this->_getBodyAsString(), true)['message'],
        );
    }

    public function testPost_imageHostNotAllowed_is400()
    {
        $this->skipNextRequestInSwagger();
        $book = $this->exampleBook();
        $book['pdfBook']['img'] = 'https://cdn.example.com/bg.png';
        $this->postBook($book);

        $this->assertResponseError($this->_getBodyAsString());
        $this->assertEquals(
            'pdfBook.img: host "cdn.example.com" is not allowed',
            json_decode($this->_getBodyAsString(), true)['message'],
        );
    }

    public function testPost_unreachableImageHost_is502WithAJsonBody()
    {
        $this->skipNextRequestInSwagger();
        // port 1 on loopback refuses immediately: deterministic, and no external network
        putenv('PDF_IMAGE_ALLOWED_HOSTS=127.0.0.1');
        try {
            $book = $this->exampleBook();
            $book['pdfBook']['img'] = 'http://127.0.0.1:1/x.png';
            $this->postBook($book);
        } finally {
            putenv('PDF_IMAGE_ALLOWED_HOSTS');
        }

        $this->assertResponseCode(502, $this->_getBodyAsString());
        $body = $this->_getBodyAsString();
        $this->assertStringStartsNotWith('%PDF-', $body);
        $this->assertIsArray(
            json_decode($body, true),
            'the client must get the JSON error body, not half a PDF: ' . $body,
        );
    }

    public function testPost_withoutAToken_isUnauthorized()
    {
        $this->skipNextRequestInSwagger();
        $this->loadAuthToken('');
        $this->post(ApiController::ROUTE_PREFIX . '/pdf', $this->exampleBook());

        // A blank bearer token doesn't reach PdfController at all: the OAuth2 library
        // rejects "Bearer " as a malformed Authorization header (400) before routing gets
        // a chance to run addNew(), rather than the 401 a well-formed-but-invalid token
        // would produce. Either way the request never executes unauthenticated.
        $this->assertResponseCode(400, $this->_getBodyAsString());
        $this->assertStringContainsString(
            'Malformed auth header',
            json_decode($this->_getBodyAsString(), true)['message'],
        );
    }

    public function testGet_isNotAllowed()
    {
        $this->skipNextRequestInSwagger();
        $this->get(ApiController::ROUTE_PREFIX . '/pdf');

        // RestApiController::getList() is not overridden, so it falls back to the base
        // class's default, which throws NotImplementedException (501) rather than a 4xx -
        // assertResponseFailure() (5xx) is the assertion that matches that, not
        // assertResponseError() (4xx).
        $this->assertResponseFailure($this->_getBodyAsString());
    }
}
