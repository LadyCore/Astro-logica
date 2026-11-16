<?php

declare(strict_types=1);

namespace App\Models;

final class DailySky
{
    /**
     * @param list<PlanetPosition> $bodies
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public readonly \DateTimeImmutable $moment,
        public readonly array $bodies,
        public readonly LunarPhase $lunarPhase,
        public readonly string $source,
        public readonly array $meta = [],
    ) {
    }

    public function body(string $name): ?PlanetPosition
    {
        foreach ($this->bodies as $body) {
            if (strcasecmp($body->name, $name) === 0 || strcasecmp($body->id, $name) === 0) {
                return $body;
            }
        }
        return null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'moment' => $this->moment->format(\DateTimeInterface::ATOM),
            'source' => $this->source,
            'lunar_phase' => $this->lunarPhase->toArray(),
            'bodies' => array_map(static fn (PlanetPosition $p) => $p->toArray(), $this->bodies),
            'meta' => $this->meta,
        ];
    }
}
