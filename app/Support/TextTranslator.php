<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Lightweight EN→ES translator (MyMemory) with on-disk cache.
 */
final class TextTranslator
{
    private string $cacheDir;

    public function __construct(?string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?? (dirname(__DIR__, 2) . '/storage/cache/translations');
    }

    public function translate(string $text, string $from = 'en', string $to = 'es'): string
    {
        $text = trim($text);
        if ($text === '' || $from === $to) {
            return $text;
        }

        $cached = $this->readCache($from, $to, $text);
        if ($cached !== null) {
            return $cached;
        }

        $translated = $this->requestMyMemory($text, $from, $to);
        if ($translated === null || $translated === '') {
            return $text;
        }

        $this->writeCache($from, $to, $text, $translated);
        return $translated;
    }

    /**
     * @param list<string> $texts
     * @return list<string>
     */
    public function translateMany(array $texts, string $from = 'en', string $to = 'es'): array
    {
        $out = [];
        foreach ($texts as $text) {
            $out[] = $this->translate((string) $text, $from, $to);
            // Gentle pacing for free tier.
            usleep(80_000);
        }
        return $out;
    }

    private function requestMyMemory(string $text, string $from, string $to): ?string
    {
        // MyMemory soft limit ~500 chars per request for best results; chunk long text.
        if (mb_strlen($text) > 450) {
            $chunks = $this->chunkSentences($text, 420);
            $parts = [];
            foreach ($chunks as $chunk) {
                $part = $this->requestMyMemory($chunk, $from, $to);
                if ($part === null) {
                    return null;
                }
                $parts[] = $part;
                usleep(80_000);
            }
            return trim(implode(' ', $parts));
        }

        $url = 'https://api.mymemory.translated.net/get?' . http_build_query([
            'q' => $text,
            'langpair' => $from . '|' . $to,
        ], '', '&', PHP_QUERY_RFC3986);

        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || !is_string($body) || $body === '') {
            return null;
        }

        $json = json_decode($body, true);
        if (!is_array($json)) {
            return null;
        }
        $translated = $json['responseData']['translatedText'] ?? null;
        if (!is_string($translated) || $translated === '') {
            return null;
        }

        // Ignore quota / invalid machine replies that echo the source.
        if (strcasecmp(trim($translated), trim($text)) === 0 && $from !== $to) {
            // Still accept exact matches for proper nouns; only reject obvious failures with ERROR.
        }
        if (str_contains(strtoupper($translated), 'MYMEMORY WARNING') || str_contains(strtoupper($translated), 'QUERY LENGTH LIMIT')) {
            return null;
        }

        return trim(html_entity_decode($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * @return list<string>
     */
    private function chunkSentences(string $text, int $maxLen): array
    {
        $parts = preg_split('/(?<=[.!?])\s+/u', $text) ?: [$text];
        $chunks = [];
        $buf = '';
        foreach ($parts as $part) {
            $candidate = $buf === '' ? $part : ($buf . ' ' . $part);
            if (mb_strlen($candidate) > $maxLen && $buf !== '') {
                $chunks[] = $buf;
                $buf = $part;
            } else {
                $buf = $candidate;
            }
        }
        if ($buf !== '') {
            $chunks[] = $buf;
        }
        return $chunks === [] ? [$text] : $chunks;
    }

    private function cachePath(string $from, string $to, string $text): string
    {
        return $this->cacheDir . '/' . $from . '_' . $to . '_' . sha1($text) . '.txt';
    }

    private function readCache(string $from, string $to, string $text): ?string
    {
        $path = $this->cachePath($from, $to, $text);
        if (!is_readable($path)) {
            return null;
        }
        $value = file_get_contents($path);
        return is_string($value) && $value !== '' ? $value : null;
    }

    private function writeCache(string $from, string $to, string $text, string $translated): void
    {
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }
        @file_put_contents($this->cachePath($from, $to, $text), $translated);
    }
}
