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

namespace App\Console\Commands;

use App\Enums\UserGroup;
use App\Models\User;
use App\Services\Unit3dAnnounce;
use Exception;
use Illuminate\Console\Command;
use Throwable;

class RestoreUsersToAutogroup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:restore-to-autogroup
                            {--dry-run : Preview how many users would be affected without applying any changes}
                            {--groups=20,28 : Comma-separated group IDs to restore users from}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore users from non-autogroup groups (e.g. Ghost, Disabled) back to the User group so auto:group can reclassify them';

    /**
     * Execute the console command.
     *
     * @throws Exception|Throwable If there is an error during the execution of the command.
     */
    final public function handle(): void
    {
        $isDryRun = $this->option('dry-run');
        $groupIds = array_map('intval', explode(',', $this->option('groups')));
        $targetGroupId = UserGroup::USER->value;

        if ($isDryRun) {
            $this->warn('[DRY-RUN] No changes will be applied.');
            $this->newLine();
        }

        foreach ($groupIds as $groupId) {
            $active = User::withTrashed()->where('group_id', $groupId)->whereNull('deleted_at')->count();
            $softDeleted = User::withTrashed()->where('group_id', $groupId)->whereNotNull('deleted_at')->count();

            $this->line(\sprintf(
                'Group ID %d → %d active users to restore, %d soft-deleted (skipped)',
                $groupId,
                $active,
                $softDeleted
            ));
        }

        $this->newLine();

        if ($isDryRun) {
            $this->comment('Dry-run complete. Run without --dry-run to apply changes.');

            return;
        }

        if (! $this->confirm(\sprintf('Restore all matching users to User group (ID %d)?', $targetGroupId))) {
            $this->info('Operation cancelled.');

            return;
        }

        $this->newLine();

        $totalMoved = 0;

        User::query()
            ->whereIntegerInRaw('group_id', $groupIds)
            ->whereNull('deleted_at')
            ->chunkById(100, function ($users) use ($targetGroupId, &$totalMoved): void {
                foreach ($users as $user) {
                    $user->update([
                        'group_id'     => $targetGroupId,
                        'can_download' => true,
                        'disabled_at'  => null,
                    ]);

                    cache()->forget('user:'.$user->passkey);

                    Unit3dAnnounce::addUser($user);

                    $totalMoved++;
                }
            });

        $this->comment(\sprintf(
            'Automated user restore command complete. %d users moved to User group (ID %d). Run "php artisan auto:group" to reclassify them immediately.',
            $totalMoved,
            $targetGroupId
        ));
    }
}
