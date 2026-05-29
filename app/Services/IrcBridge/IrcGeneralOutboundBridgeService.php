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

use App\Jobs\DeliverIrcGeneralBridgeMessage;
use App\Models\IrcBridgeMessage;
use App\Models\Message;
use Illuminate\Support\Str;

class IrcGeneralOutboundBridgeService
{
    public function __construct(
        private readonly IrcBridgeFoundationService $foundationService,
        private readonly IrcBbcodePlaintextRenderer $renderer,
    ) {
    }

    public function bridge(Message $message): ?IrcBridgeMessage
    {
        if (!$this->shouldProcess($message)) {
            return null;
        }

        $message->loadMissing(['user', 'chatroom']);
        $author = $message->user;

        $wireMessage = $this->renderer->renderWireMessage((string) $author?->username, (string) $message->message);

        if ($wireMessage === null) {
            return null;
        }

        $bridgeMessage = $this->foundationService->reserveOutbound([
            'transport'          => 'external_persistent',
            'local_message_id'   => $message->id,
            'chatroom_id'        => $message->chatroom_id,
            'remote_channel'     => $this->channel(),
            'canonical_username' => $author?->username,
            'text_plain'         => $wireMessage,
            'provenance'         => [
                'source'      => 'web',
                'message_id'  => $message->id,
                'chatroom_id' => $message->chatroom_id,
                'request_id'  => 'irc-general-outbound:'.$message->id.':'.Str::uuid(),
            ],
        ]);

        if ($bridgeMessage === null) {
            return null;
        }

        if ($this->shadowOnly()) {
            $this->transitionDeliveryState($bridgeMessage, ['reserved', 'failed'], 'shadowed');

            return $bridgeMessage->fresh();
        }

        if ($this->transitionDeliveryState($bridgeMessage, ['reserved', 'failed'], 'queued')) {
            DeliverIrcGeneralBridgeMessage::dispatchAfterResponse($bridgeMessage->id, $this->channel(), $wireMessage);
        }

        return $bridgeMessage->fresh();
    }

    private function shouldProcess(Message $message): bool
    {
        $author = $message->user;

        return $this->foundationService->shouldRecordFoundation()
            && $this->stage() >= 1
            && $message->receiver_id === null
            && $message->bot_id === null
            && $message->chatroom_id === $this->chatroomId()
            && $author !== null
            && $author->username !== null
            && $author->username !== $this->ownNick();
    }

    private function stage(): int
    {
        return (int) config('irc-bridge.general.stage', 0);
    }

    private function shadowOnly(): bool
    {
        return $this->stage() === 1;
    }

    private function chatroomId(): int
    {
        return (int) config('irc-bridge.general.chatroom_id', 1);
    }

    private function channel(): string
    {
        return (string) config('irc-bridge.general.channel', '#General');
    }

    private function ownNick(): string
    {
        return (string) config('irc-bridge.general.own_nick', 'YUS');
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
