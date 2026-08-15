<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

trait UploadResponseTrait
{
    /**
     * Use this instead of assertJsonResponseOK() whenever an upload is expected to succeed.
     *
     * The upload controllers answer 202 for every caught exception (UploadsController::respondError),
     * and 202 is inside the 200-204 range that assertResponseOk() accepts, so a rejected upload —
     * a bad token, a PDOException, anything — passes assertJsonResponseOK() unchanged. A test that
     * asserts nothing about the body then reports success while nothing was uploaded at all.
     */
    protected function assertUploadOk(string $message = ''): array
    {
        $json = $this->assertJsonResponseOK($message);
        $human = implode(' ', $json['meta']['human'] ?? []);
        $this->assertStringNotContainsString('[ERROR - ', $human, trim($message . ' upload failed:'));
        return $json;
    }

    /**
     * v2 only. v1 answers 202 for every failure by contract, so there is no status to assert there.
     */
    protected function assertUploadRejected(int $expectedStatus): array
    {
        $body = (string)$this->_getBodyAsString();
        $this->assertEquals($expectedStatus, $this->_response->getStatusCode(), $body);
        return json_decode($body, true);
    }
}
