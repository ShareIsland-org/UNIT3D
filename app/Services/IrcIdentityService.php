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

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class IrcIdentityService
{
    public function canonicalNick(User $user): string
    {
        return $user->username;
    }

    public function botAlias(User $user): string
    {
        return $user->username.config('irc-auth.allowed_bot_suffix', '_BOT');
    }

    public function classifyRequestedNick(User $user, string $requestedNick): ?string
    {
        if ($requestedNick === $this->canonicalNick($user)) {
            return 'canonical';
        }

        if ($requestedNick === $this->botAlias($user)) {
            return 'bot_alias';
        }

        return null;
    }

    public function allowedNick(User $user, string $nickType): string
    {
        return $nickType === 'bot_alias'
            ? $this->botAlias($user)
            : $this->canonicalNick($user);
    }

    public function findByCanonicalUsername(string $username): ?User
    {
        $query = User::query()
            ->withTrashed()
            ->with('group');

        if (DB::connection()->getDriverName() === 'mysql') {
            return $query->whereRaw('BINARY username = ?', [$username])->first();
        }

        $user = $query->where('username', '=', $username)->first();

        return $user?->username === $username ? $user : null;
    }
}
