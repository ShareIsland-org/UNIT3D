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
        $this->authorizeAccess($request, $user);

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
        $this->authorizeAccess($request, $user);

        $changedByStaff = $request->user()->isNot($user);

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

    /**
     * Single access rule shared by index() (reading the keys) and update() (resetting the key).
     *
     * The IRC key is a credential: whoever can read it can sign in to IRC as that user. The rule has two steps:
     *
     *  1. Who may reach the page at all: the user themselves (when irc-auth.beta_usernames is empty or lists
     *     them), or any group flagged is_modo.
     *  2. Hierarchy between two different users: the owner group (is_owner) may act on anyone, every other
     *     staff member only on users in a strictly lower level group. Peers and superiors get a 403, so a
     *     moderator can never read an administrator's key, nor another moderator's.
     *
     * The group "level" is the numeric `level` column of the groups table, so the hierarchy follows whatever
     * the staff configured there, not a fixed list of group names.
     *
     * How to change the policy (keep index() and update() on the same rule, otherwise one of them becomes a
     * way around the other):
     *  - Staff may reset a key but never read it: split this method, keep step 2 for update() and let index()
     *    abort unless the viewer is the user.
     *  - Let peers act on each other: change the `<=` in step 2 to `<`.
     *  - Drop the hierarchy entirely (original behaviour): remove step 2. Not recommended, it lets any
     *    moderator read the keys of administrators.
     *
     * Covered by tests/Feature/Http/Controllers/User/IrckeyControllerTest.php: update the tests together with
     * the rule.
     */
    private function authorizeAccess(Request $request, User $user): void
    {
        $betaUsernames = config('irc-auth.beta_usernames', []);
        $isBeta = empty($betaUsernames) || \in_array($user->username, $betaUsernames, true);

        abort_unless(
            ($request->user()->is($user) && $isBeta) || $request->user()->group->is_modo,
            403
        );

        abort_if(
            $request->user()->isNot($user)
            && !$request->user()->group->is_owner
            && $request->user()->group->level <= $user->group->level,
            403
        );
    }
}
