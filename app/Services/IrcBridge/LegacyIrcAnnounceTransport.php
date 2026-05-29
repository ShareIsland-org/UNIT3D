<?php

declare(strict_types=1);

namespace App\Services\IrcBridge;

use App\Bots\IRCAnnounceBot;
use Throwable;

class LegacyIrcAnnounceTransport
{
    /**
     * @throws Throwable
     */
    public function send(string $channel, string $message): void
    {
        (new IRCAnnounceBot())
            ->allowPilotAnnounceChannel()
            ->requireSuccessfulDelivery()
            ->to($channel)
            ->say($message);
    }
}
