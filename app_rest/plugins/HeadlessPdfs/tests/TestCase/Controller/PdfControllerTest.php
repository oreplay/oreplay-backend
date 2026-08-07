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
        return ['pdfBook' => [
            'layout' => 'default',
            'sections' => [
                ['elements' => [
                    ['type' => 'centeredElement', 'content' => 'Título del documento',
                     'size' => 36, 'position' => ['y' => 'center']],
                    ['type' => 'breakPage'],
                    ['type' => 'centeredElement', 'content' => 'Segunda página',
                     'size' => 24, 'position' => ['y' => 150]],
                ]],
                ['elements' => [
                    ['type' => 'text', 'content' => 'Otra sección',
                     'size' => 18, 'position' => ['x' => 100, 'y' => 250]],
                ]],
            ],
        ]];
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
        $this->postBook($this->exampleBook());

        $this->assertResponseOk($this->_getBodyAsString());
        $this->assertEquals('application/pdf', $this->_response->getHeaderLine('Content-Type'));
        $this->assertEquals(
            'attachment; filename="document.pdf"',
            $this->_response->getHeaderLine('Content-Disposition'),
        );
        $this->assertStringStartsWith('%PDF-', $this->_getBodyAsString());
    }

    public function testPost_usesTheRequestedFilename()
    {
        $book = $this->exampleBook();
        $book['pdfBook']['filename'] = 'certificates';
        $this->postBook($book);

        $this->assertEquals(
            'attachment; filename="certificates.pdf"',
            $this->_response->getHeaderLine('Content-Disposition'),
        );
    }

    public function testPost_unknownElementType_is400()
    {
        $this->skipNextRequestInSwagger();
        $this->post(ApiController::ROUTE_PREFIX . '/pdf', ['pdfBook' => [
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
        $this->post(ApiController::ROUTE_PREFIX . '/pdf', ['somethingElse' => 1]);

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
        $this->post(ApiController::ROUTE_PREFIX . '/pdf', $book);

        $this->assertResponseError($this->_getBodyAsString());
        $this->assertEquals(
            'pdfBook.img: host "cdn.example.com" is not allowed',
            json_decode($this->_getBodyAsString(), true)['message'],
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
