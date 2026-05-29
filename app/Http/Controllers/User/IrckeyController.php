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

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\IrckeyReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IrckeyController extends Controller
{
    /**
     * Display a users IRC keys.
     */
    public function index(Request $request, User $user): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        $betaUsernames = config('irc-auth.beta_usernames', []);
        $isBeta = empty($betaUsernames) || \in_array($user->username, $betaUsernames, true);
        abort_unless(
            ($request->user()->is($user) && $isBeta) || $request->user()->group->is_modo,
            403
        );

        return view('user.irckey.index', [
            'user'    => $user,
            'irckeys' => $user->irckeys()->latest()->get(),
        ]);
    }

    /**
     * Update user IRC key.
     */
    protected function update(Request $request, User $user): \Illuminate\Http\RedirectResponse
    {
        $betaUsernames = config('irc-auth.beta_usernames', []);
        $isBeta = empty($betaUsernames) || \in_array($user->username, $betaUsernames, true);
        abort_unless(
            ($request->user()->is($user) && $isBeta) || $request->user()->group->is_modo,
            403
        );

        $changedByStaff = $request->user()->isNot($user);

        abort_if($changedByStaff && !$request->user()->group->is_owner && $request->user()->group->level <= $user->group->level, 403);

        DB::transaction(function () use ($user, $changedByStaff): void {
            $user->irckeys()->latest()->first()?->update(['deleted_at' => now()]);

            $user->update([
                'irc_key' => bin2hex(random_bytes(16)),
            ]);

            $user->irckeys()->create(['content' => $user->irc_key]);

            if ($changedByStaff) {
                $user->notify(new IrckeyReset());
            }
        }, 5);

        return to_route('users.irckeys.index', ['user' => $user])
            ->with('success', 'Your IRC key was changed successfully.');
    }
}
