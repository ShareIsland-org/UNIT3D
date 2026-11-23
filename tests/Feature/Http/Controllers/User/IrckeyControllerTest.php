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
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 */

use App\Models\Group;
use App\Models\User;

/**
 * Creates a user in a group created on the fly, so the tests do not depend on the ids of the seeded groups.
 *
 * @param array<string, mixed> $groupAttributes
 */
function irckeyTestUser(int $level, array $groupAttributes = []): User
{
    return User::factory()->create([
        'group_id' => Group::factory()->create(['level' => $level] + $groupAttributes)->id,
    ]);
}

function irckeyTestUserGroup(): User
{
    return irckeyTestUser(30);
}

function irckeyTestModerator(): User
{
    return irckeyTestUser(2500, ['is_modo' => true]);
}

function irckeyTestAdministrator(): User
{
    return irckeyTestUser(5000, ['is_modo' => true, 'is_admin' => true]);
}

function irckeyTestOwner(): User
{
    return irckeyTestUser(9999, ['is_modo' => true, 'is_admin' => true, 'is_owner' => true]);
}

test('a user can see their own irc keys', function (): void {
    $user = irckeyTestUserGroup();

    $this->actingAs($user)
        ->get(route('users.irckeys.index', [$user]))
        ->assertOk()
        ->assertViewIs('user.irckey.index');
});

test('a regular user cannot see the irc keys of another user', function (): void {
    $viewer = irckeyTestUserGroup();
    $other = irckeyTestUserGroup();

    $this->actingAs($viewer)
        ->get(route('users.irckeys.index', [$other]))
        ->assertForbidden();
});

test('a moderator can see the irc keys of a lower level user', function (): void {
    $moderator = irckeyTestModerator();
    $user = irckeyTestUserGroup();

    $this->actingAs($moderator)
        ->get(route('users.irckeys.index', [$user]))
        ->assertOk();
});

test('a moderator cannot see the irc keys of an administrator', function (): void {
    $moderator = irckeyTestModerator();
    $admin = irckeyTestAdministrator();

    $this->actingAs($moderator)
        ->get(route('users.irckeys.index', [$admin]))
        ->assertForbidden();
});

test('a moderator cannot see the irc keys of another moderator', function (): void {
    $moderator = irckeyTestModerator();
    $peer = irckeyTestModerator();

    $this->actingAs($moderator)
        ->get(route('users.irckeys.index', [$peer]))
        ->assertForbidden();
});

test('an administrator cannot see the irc keys of the owner', function (): void {
    $admin = irckeyTestAdministrator();
    $owner = irckeyTestOwner();

    $this->actingAs($admin)
        ->get(route('users.irckeys.index', [$owner]))
        ->assertForbidden();
});

test('the owner can see the irc keys of anyone', function (): void {
    $owner = irckeyTestOwner();
    $admin = irckeyTestAdministrator();

    $this->actingAs($owner)
        ->get(route('users.irckeys.index', [$admin]))
        ->assertOk();
});
