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
use App\Models\Message;
use Illuminate\Database\QueryException;
use InvalidArgumentException;

class IrcBridgeFoundationService
{
    public function __construct(private readonly IrcBridgeIdempotencyKeyFactory $idempotencyKeyFactory)
    {
    }

    public function reserveInbound(array $attributes): ?IrcBridgeMessage
    {
        return $this->reserve('inbound', $attributes);
    }

    public function reserveOutbound(array $attributes): ?IrcBridgeMessage
    {
        return $this->reserve('outbound', $attributes);
    }

    public function linkLocalMessage(IrcBridgeMessage|int $bridgeMessage, Message|int $message): ?IrcBridgeMessage
    {
        if (!$this->shouldRecordFoundation()) {
            return null;
        }

        $bridgeMessage = $bridgeMessage instanceof IrcBridgeMessage
            ? $bridgeMessage
            : IrcBridgeMessage::query()->findOrFail($bridgeMessage);

        $message = $message instanceof Message
            ? $message
            : Message::query()->findOrFail($message);

        $bridgeMessage->forceFill([
            'local_message_id' => $message->id,
            'chatroom_id'      => $message->chatroom_id,
            'delivery_state'   => 'linked',
        ])->save();

        return $bridgeMessage->refresh();
    }

    public function foundationEnabled(): bool
    {
        return (bool) config('irc-bridge.enabled', false);
    }

    public function shouldRecordFoundation(): bool
    {
        return $this->foundationEnabled()
            && (bool) config('irc-bridge.record_foundation', false);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function reserve(string $direction, array $attributes): ?IrcBridgeMessage
    {
        if (!$this->shouldRecordFoundation()) {
            return null;
        }

        $normalized = $this->normalizeAttributes($direction, $attributes);

        $existingByExternalIdentity = $this->findByExternalIdentity($normalized);

        if ($existingByExternalIdentity !== null) {
            return $existingByExternalIdentity;
        }

        try {
            return IrcBridgeMessage::query()->firstOrCreate(
                ['idempotency_key' => $normalized['idempotency_key']],
                $normalized,
            );
        } catch (QueryException $exception) {
            if ($normalized['external_message_id'] !== null) {
                $existingByExternalIdentity = $this->findByExternalIdentity($normalized);

                if ($existingByExternalIdentity !== null) {
                    return $existingByExternalIdentity;
                }
            }

            $existingByIdempotencyKey = $this->findByIdempotencyKey($normalized['idempotency_key']);

            if ($existingByIdempotencyKey !== null) {
                return $existingByIdempotencyKey;
            }

            if ($normalized['local_message_id'] !== null) {
                $existingByLocalMessage = $this->findByLocalMessageId((int) $normalized['local_message_id']);

                if ($existingByLocalMessage !== null) {
                    return $existingByLocalMessage;
                }
            }

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $normalized
     */
    private function findByExternalIdentity(array $normalized): ?IrcBridgeMessage
    {
        if ($normalized['external_message_id'] === null) {
            return null;
        }

        return IrcBridgeMessage::query()
            ->where('direction', '=', $normalized['direction'])
            ->where('remote_channel', '=', $normalized['remote_channel'])
            ->where('external_message_id', '=', $normalized['external_message_id'])
            ->first();
    }

    private function findByIdempotencyKey(string $key): ?IrcBridgeMessage
    {
        return IrcBridgeMessage::query()->where('idempotency_key', '=', $key)->first();
    }

    private function findByLocalMessageId(int $localMessageId): ?IrcBridgeMessage
    {
        return IrcBridgeMessage::query()->where('local_message_id', '=', $localMessageId)->first();
    }

    /**
     * @param  array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function normalizeAttributes(string $direction, array $attributes): array
    {
        $transport = (string) ($attributes['transport'] ?? config('irc-bridge.transport', 'irc'));
        $provenance = $attributes['provenance'] ?? null;
        $payloadHash = $attributes['payload_hash'] ?? hash('sha256', json_encode([
            'chatroom_id'         => $attributes['chatroom_id'] ?? null,
            'remote_channel'      => $attributes['remote_channel'] ?? null,
            'external_message_id' => $attributes['external_message_id'] ?? null,
            'local_message_id'    => $attributes['local_message_id'] ?? null,
            'canonical_username'  => $attributes['canonical_username'] ?? null,
            'irc_nick'            => $attributes['irc_nick'] ?? null,
            'text_plain'          => $attributes['text_plain'] ?? $attributes['message_text'] ?? null,
        ], JSON_THROW_ON_ERROR));
        $idempotencySeed = $attributes['idempotency_seed']
            ?? (($attributes['local_message_id'] ?? null) !== null
                ? 'local-message:'.(string) $attributes['local_message_id']
                : null);

        if (($attributes['external_message_id'] ?? null) === null && $idempotencySeed === null) {
            throw new InvalidArgumentException('An external_message_id, local_message_id, or idempotency_seed is required to reserve a bridge ledger row.');
        }

        return [
            'direction'           => $direction,
            'transport'           => $transport,
            'local_message_id'    => $attributes['local_message_id'] ?? null,
            'chatroom_id'         => $attributes['chatroom_id'] ?? null,
            'remote_channel'      => $attributes['remote_channel'] ?? null,
            'external_message_id' => $attributes['external_message_id'] ?? null,
            'idempotency_key'     => $attributes['idempotency_key'] ?? $this->idempotencyKeyFactory->make(
                $direction,
                $transport,
                $attributes['remote_channel'] ?? null,
                $attributes['external_message_id'] ?? null,
                $attributes['canonical_username'] ?? null,
                $attributes['irc_nick'] ?? null,
                $idempotencySeed,
                $payloadHash,
            ),
            'canonical_username' => $attributes['canonical_username'] ?? null,
            'irc_nick'           => $attributes['irc_nick'] ?? null,
            'delivery_state'     => $attributes['delivery_state'] ?? 'reserved',
            'payload_hash'       => $payloadHash,
            'provenance'         => $provenance,
        ];
    }
}
