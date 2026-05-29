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

use App\Jobs\DeliverIrcAnnounceMessage;
use App\Models\IrcBridgeMessage;
use App\Models\Torrent;

class IrcApprovedTorrentAnnounceService
{
    public const string EVENT_KEY = 'torrent-approved';

    public function __construct(
        private readonly IrcBridgeFoundationService $foundationService,
        private readonly IrcApprovedTorrentAnnounceFormatter $formatter,
    ) {
    }

    public function publish(Torrent $torrent, ApprovedTorrentAnnounceContext $context): ?IrcBridgeMessage
    {
        if (!$this->shouldProcessEvent()) {
            return null;
        }

        if ($torrent->exists) {
            $torrent->loadMissing(['user', 'category', 'type', 'resolution']);
        }

        $detailsUrl = href_torrent($torrent);
        $message = $this->formatter->format($torrent);
        $bridgeMessage = $this->foundationService->reserveOutbound([
            'transport'           => $this->transport(),
            'remote_channel'      => $this->channel(),
            'external_message_id' => self::EVENT_KEY.':'.$torrent->id,
            'canonical_username'  => $torrent->user?->username,
            'text_plain'          => $message,
            'provenance'          => [
                'event'       => self::EVENT_KEY,
                'source'      => $context->source,
                'actor_id'    => $context->actorId,
                'torrent_id'  => $torrent->id,
                'uploader_id' => $torrent->user?->id,
                'anonymous'   => (bool) $torrent->anon,
                'details_url' => $detailsUrl,
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
            DeliverIrcAnnounceMessage::dispatch($bridgeMessage->id, $this->channel(), $message);
        }

        return $bridgeMessage->fresh();
    }

    private function shouldProcessEvent(): bool
    {
        return $this->foundationService->shouldRecordFoundation()
            && $this->eventAllowed(self::EVENT_KEY)
            && ($this->pilotEnabled() || $this->shadowOnly());
    }

    private function pilotEnabled(): bool
    {
        return (bool) config('irc-bridge.announce.pilot_enabled', false);
    }

    private function shadowOnly(): bool
    {
        return (bool) config('irc-bridge.announce.shadow_only', false);
    }

    private function transport(): string
    {
        return $this->transportMode() === 'external_persistent'
            ? 'external_persistent'
            : (string) config('irc-bridge.transport', 'irc');
    }

    private function transportMode(): string
    {
        return (string) config('irc-bridge.announce.transport_mode', 'legacy');
    }

    private function channel(): string
    {
        return (string) config('irc-bridge.announce.channel', '#announce');
    }

    private function eventAllowed(string $event): bool
    {
        return \in_array($event, config('irc-bridge.announce.allowed_events', []), true);
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
