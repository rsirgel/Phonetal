<?php

class Auth
{
    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login(string $email, string $password): bool
    {
        self::init();

        $users = [
            'admin@phonetal.sk' => [
                'name' => 'Admin',
                'password' => 'admin123',
                'role' => 'admin',
            ],
            'user@phonetal.sk' => [
                'name' => 'Zákazník',
                'password' => 'user123',
                'role' => 'pouzivatel',
            ],
        ];

        if (!isset($users[$email])) {
            return false;
        }

        $user = $users[$email];
        if ($user['password'] !== $password) {
            return false;
        }

        $_SESSION['user'] = [
            'email' => $email,
            'name' => $user['name'],
            'role' => $user['role'],
        ];

        return true;
    }

    public static function register(string $name, string $email): void
    {
        self::init();

        $_SESSION['user'] = [
            'email' => $email,
            'name' => $name,
            'role' => 'pouzivatel',
        ];
    }

    public static function logout(): void
    {
        self::init();
        unset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        self::init();
        return $_SESSION['user'] ?? null;
    }

    public static function isLoggedIn(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user && $user['role'] === 'admin';
    }
}
