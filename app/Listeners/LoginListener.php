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
 * @author     HDVinnie <hdinnovations@protonmail.com>
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 */

namespace App\Listeners;

use App\Enums\UserGroup;
use App\Models\Group;
use App\Models\User;
use App\Services\Unit3dAnnounce;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class LoginListener
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $user = $event->user;

        if ($user->group_id === UserGroup::DISABLED->value) {
            $user->group_id     = Group::query()->where('slug', 'user')->soleValue('id');
            $user->can_download = true;
            $user->disabled_at  = null;

            cache()->forget('user:'.$user->passkey);
            Unit3dAnnounce::addUser($user);

            Log::info('LoginListener: account ripristinato da Disabled a User', [
                'user_id'  => $user->id,
                'username' => $user->username,
                'via'      => $event->remember ? 'remember-me' : 'form-login',
            ]);
        }

        $user->last_login = Carbon::now();
        $user->save();
    }
}
