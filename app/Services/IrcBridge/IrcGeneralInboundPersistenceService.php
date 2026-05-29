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
use App\Models\User;
use App\Repositories\ChatRepository;

class IrcGeneralInboundPersistenceService
{
    public function __construct(
        private readonly IrcBridgeFoundationService $foundationService,
        private readonly IrcInboundNickResolver $nickResolver,
        private readonly ChatRepository $chatRepository,
    ) {
    }

    /**
     * @param  array<string, mixed>                                                                        $payload
     * @return array{ok: bool, status: string, bridge_message_id?: int, message_id?: int, reason?: string}
     */
    public function persist(array $payload): array
    {
        if ($this->stage() < 3) {
            return [
                'ok'     => true,
                'status' => 'ignored',
                'reason' => 'stage_disabled',
            ];
        }

        $rejectReason = $this->nickResolver->rejectReason((string) $payload['irc_nick']);

        if ($rejectReason !== null) {
            return [
                'ok'     => false,
                'status' => 'rejected',
                'reason' => $rejectReason,
            ];
        }

        $user = $this->nickResolver->resolve((string) $payload['irc_nick']);

        if ($user === null) {
            return [
                'ok'     => false,
                'status' => 'rejected',
                'reason' => 'unknown_user',
            ];
        }

        if (!$this->eligibleForInboundBridge($user)) {
            return [
                'ok'     => false,
                'status' => 'rejected',
                'reason' => 'chat_disabled',
            ];
        }

        $bridgeMessage = $this->foundationService->reserveInbound([
            'transport'           => 'external_persistent',
            'chatroom_id'         => $this->chatroomId(),
            'remote_channel'      => $this->channel(),
            'external_message_id' => (string) $payload['external_message_id'],
            'canonical_username'  => $user->username,
            'irc_nick'            => (string) $payload['irc_nick'],
            'text_plain'          => (string) $payload['text_plain'],
            'provenance'          => array_filter([
                'request_id'         => $payload['request_id'] ?? null,
                'received_at'        => $payload['received_at'] ?? null,
                'canonical_username' => $payload['canonical_username'] ?? null,
                'source'             => 'irc',
                'raw_provenance'     => $payload['provenance'] ?? null,
            ], static fn (mixed $value): bool => $value !== null),
        ]);

        if ($bridgeMessage === null) {
            return [
                'ok'     => true,
                'status' => 'ignored',
                'reason' => 'foundation_disabled',
            ];
        }

        if ($bridgeMessage->local_message_id !== null) {
            return [
                'ok'                => true,
                'status'            => 'duplicate',
                'bridge_message_id' => $bridgeMessage->id,
                'message_id'        => $bridgeMessage->local_message_id,
            ];
        }

        if ($this->stage() === 3) {
            if ($bridgeMessage->delivery_state === 'shadowed') {
                return [
                    'ok'                => true,
                    'status'            => 'duplicate',
                    'bridge_message_id' => $bridgeMessage->id,
                ];
            }

            $this->transitionDeliveryState($bridgeMessage, ['reserved', 'failed'], 'shadowed');

            return [
                'ok'                => true,
                'status'            => 'shadowed',
                'bridge_message_id' => $bridgeMessage->id,
            ];
        }

        $message = $this->chatRepository->bridgedInboundMessage(
            $user,
            $this->chatroomId(),
            (string) $payload['text_plain'],
            $bridgeMessage,
        );

        return [
            'ok'                => true,
            'status'            => 'accepted',
            'bridge_message_id' => $bridgeMessage->fresh()->id,
            'message_id'        => $message->id,
        ];
    }

    private function eligibleForInboundBridge(User $user): bool
    {
        $group = $user->getAttribute('group_id') !== null ? $user->group : null;
        $groupSlug = $group?->slug;
        $canChat = $user->can_chat ?? $group?->can_chat ?? false;

        return !$user->trashed()
            && !\in_array($groupSlug, ['banned', 'pruned', 'guest', 'validating', 'disabled'], true)
            && $user->disabled_at === null
            && $canChat;
    }

    private function stage(): int
    {
        return (int) config('irc-bridge.general.stage', 0);
    }

    private function chatroomId(): int
    {
        return (int) config('irc-bridge.general.chatroom_id', 1);
    }

    private function channel(): string
    {
        return (string) config('irc-bridge.general.channel', '#General');
    }

    /**
     * @param list<string> $fromStates
     */
    private function transitionDeliveryState(IrcBridgeMessage $bridgeMessage, array $fromStates, string $targetState): bool
    {
        $updated = IrcBridgeMessage::query()
            ->whereKey($bridgeMessage->id)
            ->whereIn('delivery_state', $fromStates)
            ->update(['delivery_state' => $targetState]);

        if ($updated === 1) {
            $bridgeMessage->forceFill(['delivery_state' => $targetState]);
        }

        return $updated === 1;
    }
}
