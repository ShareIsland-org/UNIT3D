<?php

declare(strict_types=1);

/**
 * NOTICE OF LICENSE.
 *
 * UNIT3D Community Edition is open-sourced software licensed under the GNU Affero General Public License v3.0
 * The details is bundled with this project in the file LICENSE.txt.
 *
 * @project    UNIT3D Community Edition
 *
 * @author     suncokret
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 */

namespace App\Services\IrcBridge;

use App\Models\IrcBridgeMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class ExternalIrcGeneralTransport
{
    public function submit(IrcBridgeMessage $bridgeMessage, string $channel, string $wireMessage): ExternalIrcGeneralTransportResult
    {
        if (!(bool) config('irc-bridge-webhook.enabled', false)) {
            return ExternalIrcGeneralTransportResult::failed(error: 'External IRC General transport is disabled.');
        }

        if ($this->baseUrl() === null) {
            return ExternalIrcGeneralTransportResult::failed(error: 'External IRC General transport is misconfigured.');
        }

        try {
            $response = $this->performRequest($channel, $bridgeMessage, $wireMessage);
        } catch (ConnectionException|RuntimeException $exception) {
            if ($this->isDeterministicConnectionFailure($exception->getMessage())) {
                return ExternalIrcGeneralTransportResult::failed(error: $exception->getMessage());
            }

            return ExternalIrcGeneralTransportResult::needsReconcile($exception->getMessage());
        } catch (Throwable $exception) {
            return ExternalIrcGeneralTransportResult::failed(error: $exception->getMessage());
        }

        if (!$response->successful()) {
            return ExternalIrcGeneralTransportResult::failed(
                $response->status(),
                $response->body() !== '' ? $response->body() : 'External IRC General transport submission failed.',
            );
        }

        $status = $response->json('status');

        if ($status === 'shadowed') {
            return ExternalIrcGeneralTransportResult::shadowed($response->status());
        }

        return ExternalIrcGeneralTransportResult::submitted($response->status());
    }

    protected function performRequest(string $channel, IrcBridgeMessage $bridgeMessage, string $wireMessage): Response
    {
        return Http::timeout((float) config('irc-bridge-webhook.timeout_seconds', 5))
            ->connectTimeout((float) config('irc-bridge-webhook.connect_timeout_seconds', 2))
            ->acceptJson()
            ->withHeaders([
                'X-API-Token' => (string) config('irc-bridge-webhook.api_token'),
            ])
            ->post($this->routeUrl(), $this->buildPayload($channel, $bridgeMessage, $wireMessage));
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPayload(string $channel, IrcBridgeMessage $bridgeMessage, string $wireMessage): array
    {
        return [
            'bridge_message_id'  => $bridgeMessage->id,
            'message_id'         => $bridgeMessage->local_message_id,
            'chatroom_id'        => $bridgeMessage->chatroom_id,
            'channel'            => $channel,
            'canonical_username' => $bridgeMessage->canonical_username,
            'wire_message'       => $wireMessage,
            'shadow_only'        => false,
            'request_id'         => $bridgeMessage->provenance['request_id'] ?? 'irc-general-'.$bridgeMessage->id,
            'idempotency_key'    => $bridgeMessage->idempotency_key,
        ];
    }

    protected function routeUrl(): string
    {
        return rtrim((string) $this->baseUrl(), '/').'/'.ltrim((string) config('irc-bridge-webhook.general_route', '/api/webhook/bridge/general'), '/');
    }

    protected function baseUrl(): ?string
    {
        $host = trim((string) config('irc-bridge-webhook.host', ''));
        $port = config('irc-bridge-webhook.port');

        if ($host !== '' && $port !== null && $port !== '') {
            return 'http://'.$host.':'.$port;
        }

        return null;
    }

    private function isDeterministicConnectionFailure(string $message): bool
    {
        return str_contains($message, 'cURL error 7')
            || str_contains($message, 'Failed to connect')
            || str_contains($message, 'Connection refused');
    }
}
