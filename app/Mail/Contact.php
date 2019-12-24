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

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class Contact extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var mixed[]
     */
    public array $input;
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $configRepository;

    /**
     * Create a new message instance.
     *
     * @param  array  $input
     */
    public function __construct(array $input, Repository $configRepository)
    {
        $this->input = $input;
        $this->configRepository = $configRepository;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        return $this->markdown('emails.contact')
            ->from($this->input['email'], $this->configRepository->get('other.title'))
            ->subject('New contact mail');
    }
}
