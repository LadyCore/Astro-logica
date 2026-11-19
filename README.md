# Astro-lógica

Premium PHP astrology web app. Public **Cielo del día (Hoy)** page with FreeAstroAPI-backed `AstroService` (mock fallback without API key).

## Visual direction

Dark cosmic UI, gold accents, mystic-woman illustration on the left, content on the right. Tagline: **Con los Pies en la Tierra y el Corazón en las Estrellas**.

## Quick start

```bash
cd astro-logica
cp .env.example .env
php -S 127.0.0.1:8080 -t public public/router.php
```

Open http://127.0.0.1:8080/hoy

## Env / API key

Copy `.env.example` → `.env` (gitignored) and set **server-side only**:

```bash
FREE_ASTRO_API_KEY=tu_api_key_aqui
```

| Variable | Purpose |
| --- | --- |
| `FREE_ASTRO_API_KEY` | Preferred FreeAstroAPI key (sent as `x-api-key`) |
| `FREEASTROAPI_KEY` / `FREEASTRO_API_KEY` | Aliases |
| `FREEASTROAPI_BASE_URL` | Default `https://api.freeastroapi.com` |

Never put the key in browser / `NEXT_PUBLIC_*` / client JS. Docs map: https://www.freeastroapi.com/llms.txt — Cielo del día uses `POST /api/v1/ephemeris/calculate`. Empty key → local mock + **Datos demo · sin API key**.

## Architecture

Controllers / Services / Models / Views. Daily sky ≠ natal chart. Natal UI is stubbed; `AstroService::getNatalChart()` is ready.

### AstroService

Path: `app/Services/AstroService.php` (`App\Services`).

```php
$astro = new \App\Services\AstroService($config); // from controllers
// or: new \App\Services\AstroService($apiKey);

$sky = $astro->getDailySky();           // DailySky model (mock if no key)
$raw = $astro->getDailySkyRaw();        // array JSON / error shape
$chart = $astro->getNatalChart([...]);  // NatalChart (requires API key)
```
