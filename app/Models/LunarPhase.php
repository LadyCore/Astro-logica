<?php

declare(strict_types=1);

namespace App\Models;

final class LunarPhase
{
    public function __construct(
        public readonly string $name,
        public readonly float $phaseAngleDeg,
        public readonly bool $isWaxing,
        public readonly string $slug,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'phase_angle_deg' => $this->phaseAngleDeg,
            'is_waxing' => $this->isWaxing,
        ];
    }
}
