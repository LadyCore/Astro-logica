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

## Env

| Variable | Purpose |
| --- | --- |
| `FREEASTROAPI_KEY` | Live FreeAstroAPI (`x-api-key`). Aliases: `FREE_ASTRO_API_KEY`, `FREEASTRO_API_KEY` |
| `FREEASTROAPI_BASE_URL` | Default `https://api.freeastroapi.com` |

## Architecture

Controllers / Services / Models / Views. Daily sky ≠ natal chart. Natal UI is stubbed; `AstroService::getNatalChart()` is ready.
