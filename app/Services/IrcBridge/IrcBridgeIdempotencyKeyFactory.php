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

class IrcBridgeIdempotencyKeyFactory
{
    public function make(
        string $direction,
        string $transport,
        ?string $remoteChannel,
        ?string $externalMessageId,
        ?string $canonicalUsername,
        ?string $ircNick,
        ?string $idempotencySeed,
        ?string $payloadHash,
    ): string {
        return 'irc-bridge:v1:'.hash('sha256', json_encode([
            'direction'           => $direction,
            'transport'           => $transport,
            'remote_channel'      => $remoteChannel,
            'external_message_id' => $externalMessageId,
            'canonical_username'  => $canonicalUsername,
            'irc_nick'            => $ircNick,
            'idempotency_seed'    => $idempotencySeed,
            'payload_hash'        => $payloadHash,
        ], JSON_THROW_ON_ERROR));
    }
}
