<?php

declare(strict_types=1);

namespace App\Models;

final class NatalChart
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public readonly array $payload,
        public readonly string $source = 'api',
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'payload' => $this->payload,
        ];
    }
}
