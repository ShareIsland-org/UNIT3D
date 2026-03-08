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

use App\Models\History;
use App\Models\Torrent;
use App\Models\TorrentReseed;
use App\Models\User;
use App\Notifications\NewReseedRequest;
use App\Repositories\ChatRepository;
use Exception;
use Illuminate\Console\Command;
use Throwable;

class AutoAddReseeds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:add_reseeds {--force : Delete existing reseed requests for still-dead torrents and re-notify}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically create reseed requests for approved torrents with few or no seeders';

    /**
     * AutoAddReseeds Constructor.
     */
    public function __construct(private readonly ChatRepository $chatRepository)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @throws Exception|Throwable If there is an error during the execution of the command.
     */
    final public function handle(): void
    {
        $maxSeeders = config('other.reseed_max_seeders');

        if ($this->option('force')) {
            TorrentReseed::whereHas(
                'torrent',
                fn ($query) => $query->where('seeders', '<=', $maxSeeders)
            )->delete();
        }

        $torrents = Torrent::query()
            ->where('seeders', '<=', $maxSeeders)
            ->whereNotIn('id', TorrentReseed::select('torrent_id'))
            ->get();

        foreach ($torrents as $torrent) {
            TorrentReseed::create([
                'torrent_id'     => $torrent->id,
                'user_id'        => 1,
                'requests_count' => 1,
            ]);

            $potentialReseeds = History::where('torrent_id', '=', $torrent->id)
                ->where('active', '=', 0)
                ->whereNotNull('completed_at')
                ->get();

            foreach ($potentialReseeds as $potentialReseed) {
                User::find($potentialReseed->user_id)?->notify(new NewReseedRequest($torrent));
            }
        }

        $count = $torrents->count();

        if ($count > 0) {
            $this->chatRepository->systemMessage(
                \sprintf('Automated reseed complete: %d reseed request(s) created. Check your notifications if you downloaded any affected torrents!', $count)
            );
        }

        $this->comment(\sprintf('Automated add reseeds command complete. Processed %d torrents.', $count));
    }
}
