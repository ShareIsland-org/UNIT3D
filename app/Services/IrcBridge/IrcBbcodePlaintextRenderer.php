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

class IrcBbcodePlaintextRenderer
{
    public function renderBody(string $message): ?string
    {
        $plainText = preg_replace('/\[quote(?:=[^\]]+)?\].*?\[\/quote\]/is', ' ', $message) ?? $message;
        $plainText = preg_replace_callback('/\[url=([^\]]+)\](.*?)\[\/url\]/is', static function (array $matches): string {
            $label = trim(strip_tags(html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            $url = trim($matches[1]);

            return $label === '' ? $url : $label.' ('.$url.')';
        }, $plainText) ?? $plainText;
        $plainText = preg_replace_callback('/\[url\](.*?)\[\/url\]/is', static fn (array $matches): string => trim($matches[1]), $plainText) ?? $plainText;
        $plainText = preg_replace('/\[img\](.*?)\[\/img\]/is', '$1', $plainText) ?? $plainText;
        $plainText = preg_replace('/\[(?:\/?)(?:b|i|u|s)\]/i', '', $plainText) ?? $plainText;
        $plainText = preg_replace('/\[(?:\/?)(?:color|size|font|left|right|center|code|spoiler)(?:=[^\]]+)?\]/i', '', $plainText) ?? $plainText;
        $plainText = preg_replace('/\[[^\]]+\]/', ' ', $plainText) ?? $plainText;
        $plainText = html_entity_decode(strip_tags($plainText), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plainText = preg_replace('/\s+/u', ' ', str_replace(["\r\n", "\r", "\n", "\t"], ' ', $plainText)) ?? $plainText;
        $plainText = trim($plainText);

        return $plainText !== '' ? $plainText : null;
    }

    public function renderWireMessage(string $username, string $message): ?string
    {
        $body = $this->renderBody($message);

        if ($body === null) {
            return null;
        }

        return '['.$username.'] '.$body;
    }
}
