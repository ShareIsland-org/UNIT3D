<?php

declare(strict_types=1);

namespace App\Services\IrcBridge;

use Carbon\CarbonImmutable;

final readonly class ExternalIrcAnnounceTransportResult
{
    private function __construct(
        public string $deliveryState,
        public ?int $responseCode,
        public ?string $error,
        public ?CarbonImmutable $submittedAt,
    ) {
    }

    public static function submitted(?int $responseCode = null, ?CarbonImmutable $submittedAt = null): self
    {
        return new self('submitted', $responseCode, null, $submittedAt ?? CarbonImmutable::now());
    }

    public static function failed(?int $responseCode = null, ?string $error = null): self
    {
        return new self('failed', $responseCode, $error, null);
    }

    public static function needsReconcile(?string $error = null): self
    {
        return new self('needs_reconcile', null, $error, null);
    }
}
