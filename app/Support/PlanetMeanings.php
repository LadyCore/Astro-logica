<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\PlanetPosition;

/**
 * Shared planet-in-sign copy (same voice as Cielo del día).
 */
final class PlanetMeanings
{
    /** @var array<string, string> */
    public const BODY_ES = [
        'sun' => 'Sol', 'moon' => 'Luna', 'mercury' => 'Mercurio', 'venus' => 'Venus', 'mars' => 'Marte',
        'jupiter' => 'Júpiter', 'saturn' => 'Saturno', 'uranus' => 'Urano', 'neptune' => 'Neptuno', 'pluto' => 'Plutón',
    ];

    /** @var array<string, string> */
    public const SIGN_ES = [
        'aries' => 'Aries', 'taurus' => 'Tauro', 'gemini' => 'Géminis', 'cancer' => 'Cáncer',
        'leo' => 'Leo', 'virgo' => 'Virgo', 'libra' => 'Libra', 'scorpio' => 'Escorpio',
        'sagittarius' => 'Sagitario', 'capricorn' => 'Capricornio', 'aquarius' => 'Acuario', 'pisces' => 'Piscis',
    ];

    /** @var array<string, string> */
    public const BODY_GLYPH = [
        'sun' => '☉', 'moon' => '☽', 'mercury' => '☿', 'venus' => '♀', 'mars' => '♂',
        'jupiter' => '♃', 'saturn' => '♄', 'uranus' => '♅', 'neptune' => '♆', 'pluto' => '♇',
    ];

    /** @var array<string, string> */
    public const SIGN_GLYPH = [
        'aries' => '♈', 'taurus' => '♉', 'gemini' => '♊', 'cancer' => '♋',
        'leo' => '♌', 'virgo' => '♍', 'libra' => '♎', 'scorpio' => '♏',
        'sagittarius' => '♐', 'capricorn' => '♑', 'aquarius' => '♒', 'pisces' => '♓',
    ];

    /** @var list<string> */
    public const PRIMARY_IDS = ['sun', 'moon', 'mercury', 'venus', 'mars', 'jupiter'];

    /** @var list<string> */
    public const SECONDARY_IDS = ['saturn', 'uranus', 'neptune', 'pluto'];

    /** @var array<string, string> */
    private const SIGN_ABBR = [
        'ari' => 'aries', 'tau' => 'taurus', 'gem' => 'gemini', 'can' => 'cancer',
        'leo' => 'leo', 'vir' => 'virgo', 'lib' => 'libra', 'sco' => 'scorpio',
        'sag' => 'sagittarius', 'cap' => 'capricorn', 'aqu' => 'aquarius', 'pis' => 'pisces',
    ];

    /**
     * @return array<string, array<string, string>>
     */
    public static function interpretations(): array
    {
        return [
            'sun' => [
                'aries' => 'Vitalidad directa: liderá con autenticidad y movimiento.',
                'taurus' => 'Estabilidad fértil: construí con paciencia y placer.',
                'gemini' => 'Mente ágil: comunicá, aprendé, conectá ideas.',
                'cancer' => 'Brillo protector: el hogar emocional es tu sol.',
                'leo' => 'Presencia magnética: creá y dejá que te vean.',
                'virgo' => 'Luz precisa: el servicio consciente también ilumina.',
                'libra' => 'Elegancia relacional: armonizá sin perderte.',
                'scorpio' => 'Fuerza íntima: transforma lo que ya no late.',
                'sagittarius' => 'Fuego filosófico: expandí sentido y horizonte.',
                'capricorn' => 'Autoridad serena: el tiempo premia tu constancia.',
                'aquarius' => 'Visión futura: innová con conciencia colectiva.',
                'pisces' => 'Inspiración fluida: soñá y canalizá lo sutil.',
            ],
            'moon' => [
                'aries' => 'Emoción inmediata: sentí y actuá sin dramatizar.',
                'taurus' => 'Calma sensorial: nutrite con lo tangible.',
                'gemini' => 'Ánimo conversador: nombrá lo que sentís.',
                'cancer' => 'Marea afectiva: cuidá tu nido interior.',
                'leo' => 'Corazón visible: necesitás reconocimiento cálido.',
                'virgo' => 'Cuidado práctico: ordená para sentirte a salvo.',
                'libra' => 'Sensibilidad estética: buscá belleza en el vínculo.',
                'scorpio' => 'Profundidad emocional: no temas la intensidad.',
                'sagittarius' => 'Ánimo viajero: la fe alivia el pecho.',
                'capricorn' => 'Contención madura: límites que abrazan.',
                'aquarius' => 'Distancia lúcida: sentí sin ahogarte.',
                'pisces' => 'Empatía oceánica: filtrá para no diluirte.',
            ],
            'mercury' => [
                'default' => 'Mente en movimiento: afiná palabras y escucha.',
            ],
            'venus' => [
                'default' => 'Afecto y belleza: elegí lo que te eleva.',
            ],
            'mars' => [
                'default' => 'Deseo en acción: canalizá el fuego con propósito.',
            ],
            'jupiter' => [
                'default' => 'Expansión guiada: crecé sin perder el centro.',
            ],
            'saturn' => [
                'default' => 'Estructura sabia: el límite también protege.',
            ],
            'uranus' => [
                'default' => 'Chispa liberadora: permití lo inesperado.',
            ],
            'neptune' => [
                'default' => 'Velo poético: intuí, soñá, no te pierdas.',
            ],
            'pluto' => [
                'default' => 'Poder profundo: transformá desde la raíz.',
            ],
        ];
    }

    public static function forBody(PlanetPosition $body): string
    {
        $id = strtolower($body->id);
        $sid = strtolower($body->signId);
        $map = self::interpretations();
        if (isset($map[$id][$sid])) {
            return $map[$id][$sid];
        }
        $base = $map[$id]['default'] ?? 'El cielo habla: escuchá su matiz.';
        if ($body->retrograde) {
            return $base . ' (Rx: revisá hacia adentro.)';
        }
        return $base;
    }

    public static function normalizeSignId(string $signOrId): string
    {
        $raw = strtolower(trim($signOrId));
        if (isset(self::SIGN_ES[$raw])) {
            return $raw;
        }
        $abbr = substr($raw, 0, 3);
        return self::SIGN_ABBR[$abbr] ?? $raw;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<PlanetPosition>
     */
    public static function positionsFromApi(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = strtolower((string) ($row['id'] ?? $row['name'] ?? ''));
            if ($id === '' || !isset(self::BODY_ES[$id])) {
                continue;
            }
            $signId = self::normalizeSignId((string) ($row['sign_id'] ?? $row['sign'] ?? ''));
            $signName = self::SIGN_ES[$signId] ?? (string) ($row['sign'] ?? $signId);
            $degree = (float) ($row['degree_in_sign'] ?? $row['pos'] ?? 0);
            $out[] = new PlanetPosition(
                id: $id,
                name: (string) ($row['name'] ?? self::BODY_ES[$id]),
                sign: $signName,
                signId: $signId,
                degree: $degree,
                retrograde: (bool) ($row['retrograde'] ?? false),
                absPos: isset($row['abs_pos']) ? (float) $row['abs_pos'] : null,
            );
        }
        return $out;
    }

    /**
     * @param list<PlanetPosition> $bodies
     * @return array{primary: list<PlanetPosition>, secondary: list<PlanetPosition>}
     */
    public static function splitPrimarySecondary(array $bodies): array
    {
        $byId = [];
        foreach ($bodies as $body) {
            $byId[strtolower($body->id)] = $body;
        }
        $primary = [];
        foreach (self::PRIMARY_IDS as $id) {
            if (isset($byId[$id])) {
                $primary[] = $byId[$id];
            }
        }
        $secondary = [];
        foreach (self::SECONDARY_IDS as $id) {
            if (isset($byId[$id])) {
                $secondary[] = $byId[$id];
            }
        }
        return ['primary' => $primary, 'secondary' => $secondary];
    }
}
