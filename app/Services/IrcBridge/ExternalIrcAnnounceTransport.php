<?php

declare(strict_types=1);

namespace App\Services\IrcBridge;

use App\Models\IrcBridgeMessage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class ExternalIrcAnnounceTransport
{
    public function submit(IrcBridgeMessage $bridgeMessage, string $channel, string $message): ExternalIrcAnnounceTransportResult
    {
        if (!$this->enabled()) {
            return ExternalIrcAnnounceTransportResult::failed(error: 'External IRC announce transport is disabled.');
        }

        if (!$this->hasValidEndpointConfiguration()) {
            return ExternalIrcAnnounceTransportResult::failed(error: 'External IRC announce transport is misconfigured.');
        }

        try {
            $response = $this->performRequest($channel, $bridgeMessage, $message);
        } catch (Throwable $exception) {
            if ($this->isDeterministicSubmissionFailure($exception)) {
                return ExternalIrcAnnounceTransportResult::failed(error: $exception->getMessage());
            }

            return ExternalIrcAnnounceTransportResult::needsReconcile($exception->getMessage());
        }

        if (!$response->successful()) {
            return ExternalIrcAnnounceTransportResult::failed(
                responseCode: $response->status(),
                error: $response->body() !== '' ? $response->body() : 'External IRC announce transport returned a non-success response.',
            );
        }

        return ExternalIrcAnnounceTransportResult::submitted($response->status());
    }

    protected function buildPayload(IrcBridgeMessage $bridgeMessage, string $message): array
    {
        $provenance = $bridgeMessage->provenance ?? [];

        return [
            'id'        => (int) ($provenance['torrent_id'] ?? $bridgeMessage->id),
            'url'       => (string) ($provenance['details_url'] ?? ''),
            'name'      => $message,
            'uploader'  => (string) ($bridgeMessage->canonical_username ?? ($provenance['anonymous'] ?? false ? 'Anonymous' : 'Unknown')),
            'category'  => '',
            'size'      => '',
            'internal'  => false,
            'freeleech' => false,
            'double_up' => false,
            'data'      => [
                'bridge_message_id'   => $bridgeMessage->id,
                'external_message_id' => $bridgeMessage->external_message_id,
                'payload_hash'        => $bridgeMessage->payload_hash,
                'formatted_message'   => $message,
                'provenance'          => $provenance,
            ],
        ];
    }

    protected function performRequest(string $channel, IrcBridgeMessage $bridgeMessage, string $message): Response
    {
        return $this->buildHttpClient()->post($this->buildRoute($channel), $this->buildPayload($bridgeMessage, $message));
    }

    protected function buildRoute(string $channel): string
    {
        $channelRoute = (string) (config('irc-bot-external.channel_route') ?: ltrim($channel, '#'));
        $unixSocket = config('irc-bot-external.unix_socket');

        $base = \is_string($unixSocket) && $unixSocket !== ''
            ? 'http://localhost'
            : 'http://'.config('irc-bot-external.host').':'.config('irc-bot-external.port');

        return rtrim($base, '/').'/api/webhook/announce/'.trim($channelRoute, '/');
    }

    protected function buildHttpClient(): PendingRequest
    {
        $client = Http::createPendingRequest()
            ->timeout((float) config('irc-bot-external.timeout_seconds', 5))
            ->connectTimeout((float) config('irc-bot-external.connect_timeout_seconds', 2))
            ->acceptJson();

        $apiKey = (string) config('irc-bot-external.api_key', '');

        if ($apiKey !== '') {
            $client = $client->withHeaders(['X-API-Token' => $apiKey]);
        }

        $unixSocket = config('irc-bot-external.unix_socket');

        if (\is_string($unixSocket) && $unixSocket !== '') {
            $client = $client->withOptions([
                'curl' => [
                    CURLOPT_UNIX_SOCKET_PATH => $unixSocket,
                ],
            ]);
        }

        return $client;
    }

    protected function enabled(): bool
    {
        return (bool) config('irc-bot-external.enabled', false);
    }

    protected function hasValidEndpointConfiguration(): bool
    {
        $unixSocket = config('irc-bot-external.unix_socket');
        $host = config('irc-bot-external.host');
        $port = config('irc-bot-external.port');

        if (\is_string($unixSocket) && $unixSocket !== '') {
            return true;
        }

        return \is_string($host)
            && $host !== ''
            && is_numeric((string) $port)
            && (int) $port > 0;
    }

    protected function isDeterministicSubmissionFailure(Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        foreach ([
            'connection refused',
            'could not connect to server',
            'failed to connect',
            'could not resolve host',
            'name or service not known',
            'no such file or directory',
            'getaddrinfo',
        ] as $fragment) {
            if (str_contains($message, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
