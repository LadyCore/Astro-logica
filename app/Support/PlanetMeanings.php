<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\PlanetPosition;

/**
 * Shared planet-in-sign copy (same voice as Cielo del día), ES/EN.
 */
final class PlanetMeanings
{
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

    /** @var array<string, string> */
    private const BODY_IDS = [
        'sun' => true, 'moon' => true, 'mercury' => true, 'venus' => true, 'mars' => true,
        'jupiter' => true, 'saturn' => true, 'uranus' => true, 'neptune' => true, 'pluto' => true,
    ];

    /**
     * @return array<string, array<string, string>>
     */
    public static function interpretations(string $locale = 'es'): array
    {
        return I18n::normalize($locale) === 'en'
            ? self::interpretationsEn()
            : self::interpretationsEs();
    }

    public static function forBody(PlanetPosition $body, string $locale = 'es'): string
    {
        $locale = I18n::normalize($locale);
        $id = strtolower($body->id);
        $sid = strtolower($body->signId);
        $map = self::interpretations($locale);
        if (isset($map[$id][$sid])) {
            $text = $map[$id][$sid];
        } else {
            $text = $map[$id]['default'] ?? ($locale === 'en'
                ? 'The sky speaks: listen to its nuance.'
                : 'El cielo habla: escuchá su matiz.');
        }
        if ($body->retrograde) {
            $text .= ' ' . ($locale === 'en' ? '(Rx: look inward.)' : '(Rx: revisá hacia adentro.)');
        }
        return $text;
    }

    public static function bodyName(string $id, string $locale = 'es'): string
    {
        $id = strtolower($id);
        $names = I18n::normalize($locale) === 'en'
            ? [
                'sun' => 'Sun', 'moon' => 'Moon', 'mercury' => 'Mercury', 'venus' => 'Venus', 'mars' => 'Mars',
                'jupiter' => 'Jupiter', 'saturn' => 'Saturn', 'uranus' => 'Uranus', 'neptune' => 'Neptune', 'pluto' => 'Pluto',
            ]
            : [
                'sun' => 'Sol', 'moon' => 'Luna', 'mercury' => 'Mercurio', 'venus' => 'Venus', 'mars' => 'Marte',
                'jupiter' => 'Júpiter', 'saturn' => 'Saturno', 'uranus' => 'Urano', 'neptune' => 'Neptuno', 'pluto' => 'Plutón',
            ];
        return $names[$id] ?? $id;
    }

    public static function signName(string $signId, string $locale = 'es'): string
    {
        $signId = self::normalizeSignId($signId);
        $names = I18n::normalize($locale) === 'en'
            ? [
                'aries' => 'Aries', 'taurus' => 'Taurus', 'gemini' => 'Gemini', 'cancer' => 'Cancer',
                'leo' => 'Leo', 'virgo' => 'Virgo', 'libra' => 'Libra', 'scorpio' => 'Scorpio',
                'sagittarius' => 'Sagittarius', 'capricorn' => 'Capricorn', 'aquarius' => 'Aquarius', 'pisces' => 'Pisces',
            ]
            : [
                'aries' => 'Aries', 'taurus' => 'Tauro', 'gemini' => 'Géminis', 'cancer' => 'Cáncer',
                'leo' => 'Leo', 'virgo' => 'Virgo', 'libra' => 'Libra', 'scorpio' => 'Escorpio',
                'sagittarius' => 'Sagitario', 'capricorn' => 'Capricornio', 'aquarius' => 'Acuario', 'pisces' => 'Piscis',
            ];
        return $names[$signId] ?? $signId;
    }

    public static function normalizeSignId(string $signOrId): string
    {
        $raw = strtolower(trim($signOrId));
        $known = [
            'aries', 'taurus', 'gemini', 'cancer', 'leo', 'virgo',
            'libra', 'scorpio', 'sagittarius', 'capricorn', 'aquarius', 'pisces',
        ];
        if (in_array($raw, $known, true)) {
            return $raw;
        }
        $abbr = substr($raw, 0, 3);
        return self::SIGN_ABBR[$abbr] ?? $raw;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<PlanetPosition>
     */
    public static function positionsFromApi(array $rows, string $locale = 'es'): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = strtolower((string) ($row['id'] ?? $row['name'] ?? ''));
            if ($id === '' || !isset(self::BODY_IDS[$id])) {
                continue;
            }
            $signId = self::normalizeSignId((string) ($row['sign_id'] ?? $row['sign'] ?? ''));
            $degree = (float) ($row['degree_in_sign'] ?? $row['pos'] ?? 0);
            $out[] = new PlanetPosition(
                id: $id,
                name: self::bodyName($id, $locale),
                sign: self::signName($signId, $locale),
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

    /**
     * @return array<string, array<string, string>>
     */
    private static function interpretationsEs(): array
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
            'mercury' => ['default' => 'Mente en movimiento: afiná palabras y escucha.'],
            'venus' => ['default' => 'Afecto y belleza: elegí lo que te eleva.'],
            'mars' => ['default' => 'Deseo en acción: canalizá el fuego con propósito.'],
            'jupiter' => ['default' => 'Expansión guiada: crecé sin perder el centro.'],
            'saturn' => ['default' => 'Estructura sabia: el límite también protege.'],
            'uranus' => ['default' => 'Chispa liberadora: permití lo inesperado.'],
            'neptune' => ['default' => 'Velo poético: intuí, soñá, no te pierdas.'],
            'pluto' => ['default' => 'Poder profundo: transformá desde la raíz.'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private static function interpretationsEn(): array
    {
        return [
            'sun' => [
                'aries' => 'Direct vitality: lead with authenticity and movement.',
                'taurus' => 'Fertile stability: build with patience and pleasure.',
                'gemini' => 'Agile mind: communicate, learn, connect ideas.',
                'cancer' => 'Protective radiance: the emotional home is your sun.',
                'leo' => 'Magnetic presence: create and let yourself be seen.',
                'virgo' => 'Precise light: conscious service also shines.',
                'libra' => 'Relational elegance: harmonize without losing yourself.',
                'scorpio' => 'Intimate strength: transform what no longer lives.',
                'sagittarius' => 'Philosophical fire: expand meaning and horizon.',
                'capricorn' => 'Quiet authority: time rewards your constancy.',
                'aquarius' => 'Future vision: innovate with collective awareness.',
                'pisces' => 'Fluid inspiration: dream and channel the subtle.',
            ],
            'moon' => [
                'aries' => 'Immediate emotion: feel and act without drama.',
                'taurus' => 'Sensory calm: nourish yourself with the tangible.',
                'gemini' => 'Conversational mood: name what you feel.',
                'cancer' => 'Affective tide: tend your inner nest.',
                'leo' => 'Visible heart: you need warm recognition.',
                'virgo' => 'Practical care: order to feel safe.',
                'libra' => 'Aesthetic sensitivity: seek beauty in connection.',
                'scorpio' => 'Emotional depth: do not fear intensity.',
                'sagittarius' => 'Traveling spirit: faith eases the chest.',
                'capricorn' => 'Mature containment: boundaries that embrace.',
                'aquarius' => 'Lucid distance: feel without drowning.',
                'pisces' => 'Oceanic empathy: filter so you do not dissolve.',
            ],
            'mercury' => ['default' => 'Mind in motion: refine words and listening.'],
            'venus' => ['default' => 'Affection and beauty: choose what elevates you.'],
            'mars' => ['default' => 'Desire in action: channel the fire with purpose.'],
            'jupiter' => ['default' => 'Guided expansion: grow without losing center.'],
            'saturn' => ['default' => 'Wise structure: the boundary also protects.'],
            'uranus' => ['default' => 'Liberating spark: allow the unexpected.'],
            'neptune' => ['default' => 'Poetic veil: intuit, dream, do not get lost.'],
            'pluto' => ['default' => 'Deep power: transform from the root.'],
        ];
    }
}
