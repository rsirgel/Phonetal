<?php
require_once __DIR__ . '/integrations.php';

class GoogleAuth
{
    private static function config(): array
    {
        static $config;
        if ($config === null) {
            $config = loadIntegrationsConfig();
        }
        return $config;
    }

    public static function isConfigured(): bool
    {
        return self::clientId() !== '' && self::clientSecret() !== '' && self::redirectUri() !== '';
    }

    public static function clientId(): string
    {
        return getIntegrationSetting(self::config(), 'GOOGLE_CLIENT_ID');
    }

    public static function clientSecret(): string
    {
        return getIntegrationSetting(self::config(), 'GOOGLE_CLIENT_SECRET');
    }

    public static function redirectUri(): string
    {
        return getIntegrationSetting(self::config(), 'GOOGLE_REDIRECT_URI');
    }

    public static function buildAuthUrl(string $state): string
    {
        $query = http_build_query([
            'client_id' => self::clientId(),
            'redirect_uri' => self::redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $query;
    }

    public static function fetchUserByCode(string $code): ?array
    {
        $tokenBody = self::postForm('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => self::clientId(),
            'client_secret' => self::clientSecret(),
            'redirect_uri' => self::redirectUri(),
            'grant_type' => 'authorization_code',
        ]);
        if ($tokenBody === null) {
            return null;
        }

        $token = json_decode($tokenBody, true);
        if (!is_array($token) || empty($token['access_token'])) {
            return null;
        }

        $userBody = self::getWithBearer('https://openidconnect.googleapis.com/v1/userinfo', (string) $token['access_token']);
        if ($userBody === null) {
            return null;
        }

        $user = json_decode($userBody, true);
        if (!is_array($user) || empty($user['email'])) {
            return null;
        }

        return $user;
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

    private static function getWithBearer(string $url, string $token): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return null;
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
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
                'method' => 'GET',
                'header' => "Authorization: Bearer {$token}\r\n",
                'timeout' => 10,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);
        return is_string($result) ? $result : null;
    }
}
