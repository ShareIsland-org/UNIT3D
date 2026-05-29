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

namespace App\Services\IrcBridge;

use App\Models\Torrent;

class IrcApprovedTorrentAnnounceFormatter
{
    public function format(Torrent $torrent): string
    {
        $uploader = $torrent->anon
            ? 'An anonymous user'
            : ($torrent->user?->username ?? 'System');

        $category = $torrent->category?->name ?? 'Unknown';
        $type = $torrent->type?->name ?? 'Unknown';
        $resolution = $torrent->resolution?->name ?? 'N/A';
        $size = str_replace("\u{00A0}", ' ', $torrent->getSize());
        $freeleech = (string) $torrent->free;
        $link = href_torrent($torrent);

        return \sprintf(
            '[%s] has uploaded [%s]. Category: [%s] Type: [%s] Resolution: [%s] Size: [%s] Freeleech: [%s] Link: [%s]',
            $this->normalizeField($uploader),
            $this->normalizeField($torrent->name),
            $this->normalizeField($category),
            $this->normalizeField($type),
            $this->normalizeField($resolution),
            $this->normalizeField($size),
            $this->normalizeField($freeleech),
            $this->normalizeField($link),
        );
    }

    private function normalizeField(string $value): string
    {
        $normalized = str_replace(["\r", "\n", "\t", '[', ']'], [' ', ' ', ' ', '(', ')'], $value);
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }
}
