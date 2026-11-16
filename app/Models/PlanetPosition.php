<?php

declare(strict_types=1);

namespace App\Models;

final class PlanetPosition
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $sign,
        public readonly string $signId,
        public readonly float $degree,
        public readonly bool $retrograde,
        public readonly ?string $positionText = null,
        public readonly ?float $absPos = null,
    ) {
    }

    public function degreeFormatted(): string
    {
        $deg = (int) floor($this->degree);
        $minutes = (int) round(($this->degree - $deg) * 60);
        if ($minutes === 60) {
            $deg += 1;
            $minutes = 0;
        }
        return sprintf("%d°%02d'", $deg, $minutes);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sign' => $this->sign,
            'sign_id' => $this->signId,
            'degree' => $this->degree,
            'degree_formatted' => $this->degreeFormatted(),
            'retrograde' => $this->retrograde,
            'position_text' => $this->positionText ?? ($this->degreeFormatted() . ' ' . $this->sign),
            'abs_pos' => $this->absPos,
        ];
    }
}
