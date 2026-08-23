<?php

declare(strict_types = 1);

namespace Results\Lib\Publish;

use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Log\LogTrait;
use Psr\Log\LogLevel;

/**
 * Publishes through the nchan endpoint inside the pod, which the Kubernetes service does not expose.
 *
 * A push is a courtesy to whoever is watching, never a reason to fail an upload: the results are already
 * committed by the time this runs, so a refused connection or a slow socket must cost a log line and
 * nothing else. See docs/realtime-and-async-uploads.md.
 */
class NchanChannel implements ChannelPublisher
{
    use LogTrait;

    public const DEFAULT_URL = 'http://127.0.0.1:8080/internal/publish/';
    private const TIMEOUT_SECONDS = 2;

    public function __construct(private readonly ?Client $http = null)
    {
    }

    public static function isConfigured(): bool
    {
        return (bool)self::_baseUrl();
    }

    private static function _baseUrl(): string
    {
        return (string)Configure::read('Nchan.publishUrl', self::DEFAULT_URL);
    }

    public function publish(string $channel, array $payload): void
    {
        $url = self::_baseUrl() . $channel;
        try {
            $client = $this->http ?: new Client(['timeout' => self::TIMEOUT_SECONDS]);
            $response = $client->post($url, json_encode($payload), ['type' => 'json']);
            if (!$response->isOk()) {
                $this->log('Nchan refused a publish to ' . $url . ': ' . $response->getStatusCode(),
                    LogLevel::WARNING);
            }
        } catch (\Throwable $e) {
            $this->log('Nchan publish failed for ' . $url . ': ' . $e->getMessage(), LogLevel::WARNING);
        }
    }
}
