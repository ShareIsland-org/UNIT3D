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

use App\Models\User;

class IrcInboundNickResolver
{
    public function rejectReason(string $ircNick): ?string
    {
        $normalizedNick = mb_strtolower(trim($ircNick));
        $ownNick = mb_strtolower((string) config('irc-bridge.general.own_nick', 'YUS'));
        $botSuffix = mb_strtolower((string) config('irc-bridge.general.reject_bot_suffix', '_BOT'));

        if ($normalizedNick === $ownNick) {
            return 'own_nick';
        }

        if ($botSuffix !== '' && str_ends_with($normalizedNick, $botSuffix)) {
            return 'bot_nick';
        }

        return null;
    }

    public function resolve(string $ircNick): ?User
    {
        return User::query()
            ->whereRaw('LOWER(username) = ?', [mb_strtolower(trim($ircNick))])
            ->first();
    }
}
