<?php

declare(strict_types = 1);

namespace Results\Lib\Publish;

/**
 * Sends a payload to everyone listening on a channel. Kept behind an interface so the import can be tested
 * without a running nchan, and so an environment without one can be given a publisher that does nothing.
 */
interface ChannelPublisher
{
    public function publish(string $channel, array $payload): void;
}
