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

namespace App\Jobs;

use App\Models\IrcBridgeMessage;
use App\Services\IrcBridge\ExternalIrcGeneralTransport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class DeliverIrcGeneralBridgeMessage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $bridgeMessageId,
        public string $channel,
        public string $wireMessage,
    ) {
    }

    /**
     * @return array<int, WithoutOverlapping>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('irc-general-bridge-message:'.$this->bridgeMessageId))->shared(),
        ];
    }

    public function handle(ExternalIrcGeneralTransport $transport): void
    {
        $bridgeMessage = IrcBridgeMessage::query()->findOrFail($this->bridgeMessageId);

        if ($this->shouldSkipSubmission($bridgeMessage) || !\in_array($bridgeMessage->delivery_state, ['queued', 'failed'], true)) {
            return;
        }

        $result = $transport->submit($bridgeMessage, $this->channel, $this->wireMessage);

        $bridgeMessage->forceFill([
            'delivery_state'          => $result->deliveryState,
            'transport_submission_at' => $result->submittedAt,
            'transport_response_code' => $result->responseCode,
            'transport_error'         => $result->error,
        ])->save();

        if ($result->deliveryState === 'failed') {
            throw new RuntimeException($result->error ?? 'External IRC General transport submission failed.');
        }
    }

    private function shouldSkipSubmission(IrcBridgeMessage $bridgeMessage): bool
    {
        return \in_array($bridgeMessage->delivery_state, ['submitted', 'needs_reconcile', 'shadowed', 'sent', 'linked'], true);
    }
}
