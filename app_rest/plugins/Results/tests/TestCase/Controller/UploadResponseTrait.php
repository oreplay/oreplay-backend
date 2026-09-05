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
     *
     * v1 still says so in meta.human. In v2 the marker is meta.uploadType, which only an answer that got
     * as far as reading the payload can carry — and not meta.level, which says how good the outcome was:
     * an upload that stored everything but merged two runners answers 200 and level error, on purpose.
     */
    protected function assertUploadOk(string $message = ''): array
    {
        $json = $this->assertJsonResponseOK($message);
        $failed = trim($message . ' upload failed:');
        if (array_key_exists('uploadType', $json['meta'] ?? [])) {
            $this->assertNotNull($json['meta']['uploadType'],
                $failed . ' ' . json_encode($json['meta']['messages'] ?? []));
            return $json;
        }
        $human = implode(' ', $json['meta']['human'] ?? []);
        $this->assertStringNotContainsString('[ERROR - ', $human, $failed);
        return $json;
    }

    /**
     * v2 only. Compares meta without the parts a test cannot pin: timings vary per run, uploadId is a
     * fresh uuid each time, uploadType only restates what the payload asked for, and `_c` is the schema
     * marker the OpenAPI capture reads, pinned once in its own test rather than in every expectation.
     */
    protected function assertUploadMeta(array $expected, array $json): void
    {
        $meta = $this->_withoutSchemaMarkers($json['meta']);
        unset($meta['timings'], $meta['uploadType'], $meta['uploadId']);
        $this->assertEquals($expected, $meta);
    }

    private function _withoutSchemaMarkers(array $data): array
    {
        unset($data['_c']);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->_withoutSchemaMarkers($value);
            }
        }
        return $data;
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
