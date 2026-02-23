<?php

function loadIntegrationsConfig(): array
{
    $configFile = __DIR__ . '/../config/integrations.php';
    if (!is_file($configFile)) {
        return [];
    }

    $config = require $configFile;
    return is_array($config) ? $config : [];
}

function getIntegrationSetting(array $config, string $envKey, string $fallback = ''): string
{
    if (array_key_exists($envKey, $config) && $config[$envKey] !== null && $config[$envKey] !== '') {
        return trim((string) $config[$envKey]);
    }

    $value = getenv($envKey);
    if ($value !== false && $value !== '') {
        return trim((string) $value);
    }

    return $fallback;
}
