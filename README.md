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

Copy `.env.example` → `.env` (gitignored) and set:

```bash
FREEASTROAPI_KEY=tu_api_key_aqui
```

| Variable | Purpose |
| --- | --- |
| `FREEASTROAPI_KEY` | Live FreeAstroAPI (`x-api-key`). Aliases: `FREE_ASTRO_API_KEY`, `FREEASTRO_API_KEY` |
| `FREEASTROAPI_BASE_URL` | Default `https://api.freeastroapi.com` |

Get a key from the FreeAstroAPI dashboard. With the key set, `getDailySky()` calls the real ephemeris API; empty key keeps the local mock and shows **Datos demo · sin API key** on Hoy.

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
