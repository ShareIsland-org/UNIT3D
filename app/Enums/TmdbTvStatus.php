<?php

/**
 * NOTICE OF LICENSE.
 *
 * UNIT3D Community Edition is open-sourced software licensed under the GNU Affero General Public License v3.0
 * The details is bundled with this project in the file LICENSE.txt.
 *
 * @project    UNIT3D Community Edition
 *
 * @author     braingremlin85 <braingremlin@protonmail.com>
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 */

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

enum TmdbTvStatus: string
{
    case RETURNINGSERIES = 'Returning Series';
    case PLANNED         = 'Planned';
    case INPRODUCTION    = 'In Production';
    case ENDED           = 'Ended';
    case CANCELED        = 'Canceled';
    case PILOT           = 'Pilot';

    public function icon(): string
    {
        return match ($this) {
            self::RETURNINGSERIES => 'fa-play',
            self::PLANNED         => 'fa-calendar',
            self::INPRODUCTION    => 'fa-clapperboard',
            self::ENDED           => 'fa-stop',
            self::CANCELED        => 'fa-times',
            self::PILOT           => 'fa-camera-movie',
        };
    }

    /**
     * class slug derived from the case name, e.g. ReturningSeries -> returning-series.
     */
    public function class(): string
    {
        return Str::kebab($this->value);
    }
        
}