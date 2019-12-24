<?php
/**
 * NOTICE OF LICENSE.
 *
 * UNIT3D is open-sourced software licensed under the GNU Affero General Public License v3.0
 * The details is bundled with this project in the file LICENSE.txt.
 *
 * @project    UNIT3D
 *
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 * @author     HDVinnie
 */

namespace App\Console\Commands;

use App\Models\Graveyard;
use App\Models\History;
use App\Models\Message;
use App\Models\PrivateMessage;
use App\Models\Torrent;
use App\Models\User;
use App\Repositories\ChatRepository;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;

final class AutoGraveyard extends Command
{
    /**
     * @var ChatRepository
     */
    private ChatRepository $chat;
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $configRepository;

    public function __construct(ChatRepository $chat, Repository $configRepository)
    {
        parent::__construct();

        $this->chat = $chat;
        $this->configRepository = $configRepository;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected string $signature = 'auto:graveyard';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Automatically Checks Graveyard Records For Succesful Ressurections';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void
    {
        $rewardable = Graveyard::where('rewarded', '!=', 1)->oldest()->get();

        foreach ($rewardable as $reward) {
            $user = User::where('id', '=', $reward->user_id)->first();

            $torrent = Torrent::where('id', '=', $reward->torrent_id)->first();

            if (isset($user) && isset($torrent)) {
                $history = History::where('info_hash', '=', $torrent->info_hash)
                    ->where('user_id', '=', $user->id)
                    ->where('seedtime', '>=', $reward->seedtime)
                    ->first();
            }

            if (isset($history)) {
                $reward->rewarded = 1;
                $reward->save();

                $user->fl_tokens += $this->configRepository->get('graveyard.reward');
                $user->save();

                // Auto Shout
                $appurl = $this->configRepository->get('app.url');

                $this->chat->systemMessage(
                    sprintf('Ladies and Gents, [url=%s/users/%s]%s[/url] has successfully resurrected [url=%s/torrents/%s.%s]%s[/url]. :zombie:', $appurl, $user->username, $user->username, $appurl, $torrent->slug, $torrent->id, $torrent->name)
                );

                // Send Private Message
                $pm = new PrivateMessage();
                $pm->sender_id = 1;
                $pm->receiver_id = $user->id;
                $pm->subject = 'Successful Graveyard Resurrection';
                $pm->message = sprintf('You have successfully resurrected [url=%s/torrents/', $appurl).$torrent->id.']'.$torrent->name.'[/url] :zombie: ! Thank you for bringing a torrent back from the dead! Enjoy the freeleech tokens!
                [color=red][b]THIS IS AN AUTOMATED SYSTEM MESSAGE, PLEASE DO NOT REPLY![/b][/color]';
                $pm->save();
            }
        }
    }
}
