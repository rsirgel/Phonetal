<?php
require_once __DIR__ . '/integrations.php';

class Recaptcha
{
    private static function config(): array
    {
        static $config;
        if ($config === null) {
            $config = loadIntegrationsConfig();
        }
        return $config;
    }

    public static function siteKey(): string
    {
        return getIntegrationSetting(self::config(), 'RECAPTCHA_SITE_KEY');
    }

    public static function secretKey(): string
    {
        return getIntegrationSetting(self::config(), 'RECAPTCHA_SECRET_KEY');
    }

    public static function isConfigured(): bool
    {
        return self::siteKey() !== '' && self::secretKey() !== '';
    }

    public static function verifyToken(
        string $token,
        ?string $remoteIp = null,
        ?string $expectedAction = null,
        float $minScore = 0.0
    ): bool
    {
        $secret = self::secretKey();
        if ($secret === '' || $token === '') {
            return false;
        }

        $payload = [
            'secret' => $secret,
            'response' => $token,
        ];

        if ($remoteIp !== null && $remoteIp !== '') {
            $payload['remoteip'] = $remoteIp;
        }

        $responseBody = self::postForm('https://www.google.com/recaptcha/api/siteverify', $payload);
        if ($responseBody === null) {
            return false;
        }

        $decoded = json_decode($responseBody, true);
        if (!is_array($decoded) || empty($decoded['success'])) {
            return false;
        }

        if ($expectedAction !== null && $expectedAction !== '') {
            $action = (string) ($decoded['action'] ?? '');
            if ($action !== $expectedAction) {
                return false;
            }
        }

        if ($minScore > 0) {
            $score = $decoded['score'] ?? null;
            if (!is_numeric($score) || (float) $score < $minScore) {
                return false;
            }
        }

        return true;
    }

    private static function postForm(string $url, array $payload): ?string
    {
        $encoded = http_build_query($payload);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return null;
            }

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $encoded,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            ]);

            $result = curl_exec($ch);
            $error = curl_errno($ch);
            curl_close($ch);

            if ($error || !is_string($result)) {
                return null;
            }

            return $result;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $encoded,
                'timeout' => 10,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);
        return is_string($result) ? $result : null;
    }
}
