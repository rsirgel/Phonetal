<?php

require_once __DIR__ . '/../config/database.php';

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
        $database = new Database();
        $user = $database->fetchUserByEmail($email);
        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => trim($user['meno'] . ' ' . $user['priezvisko']),
            'first_name' => $user['meno'],
            'last_name' => $user['priezvisko'],
            'role' => $user['rola'],
            'phone' => $user['telefon'],
            'city' => $user['mesto'],
            'street' => $user['ulica'],
            'birth_number' => $user['rodne_cislo'],
        ];

        return true;
    }

    public static function register(string $firstName, string $lastName, string $email, string $password): void
    {
        self::init();

        $database = new Database();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $userId = $database->createUser([
            'meno' => $firstName,
            'priezvisko' => $lastName,
            'email' => $email,
            'password_hash' => $passwordHash,
            'telefon' => null,
            'rodne_cislo' => null,
            'mesto' => null,
            'ulica' => null,
            'rola' => 'pouzivatel',
        ]);

        $_SESSION['user'] = [
            'id' => $userId,
            'email' => $email,
            'name' => trim($firstName . ' ' . $lastName),
            'first_name' => $firstName,
            'last_name' => $lastName,
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