<?php

require_once __DIR__ . '/../config/database.php';

class Auth
{
    private static function establishSession(array $user): void
    {
        session_regenerate_id(true);
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
            'postal_code' => $user['psc'],
            'iban' => $user['iban'],
            'bic' => $user['bic'],
            'account_owner' => $user['meno_uctu'],
            'birth_number' => $user['rodne_cislo'],
        ];
    }

    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
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

        self::establishSession($user);

        return true;
    }

    public static function loginExistingUserByEmail(string $email): bool
    {
        self::init();
        $database = new Database();
        $user = $database->fetchUserByEmail($email);
        if (!$user) {
            return false;
        }

        self::establishSession($user);
        return true;
    }

    public static function register(string $firstName, string $lastName, string $email, string $password): void
    {
        self::init();

        $database = new Database();
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('invalid_email');
        }

        if (strlen($password) < 6) {
            throw new \InvalidArgumentException('weak_password');
        }

        if ($database->fetchUserByEmail($email) !== null) {
            throw new \RuntimeException('email_exists');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $userId = $database->createUser([
                'meno' => $firstName,
                'priezvisko' => $lastName,
                'email' => $email,
                'password_hash' => $passwordHash,
                'telefon' => null,
                'rodne_cislo' => null,
                'mesto' => null,
                'ulica' => null,
                'psc' => 0,
                'iban' => '',
                'bic' => 0,
                'meno_uctu' => '',
                'rola' => 'pouzivatel',
            ]);
        } catch (\Throwable $exception) {
            $error = strtolower($exception->getMessage());
            if (str_contains($error, 'duplicate') && str_contains($error, 'email')) {
                throw new \RuntimeException('email_exists', 0, $exception);
            }
            throw $exception;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $userId,
            'email' => $email,
            'name' => trim($firstName . ' ' . $lastName),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => 'pouzivatel',
            'postal_code' => null,
            'iban' => null,
            'bic' => null,
            'account_owner' => null,
        ];
    }

    public static function logout(): void
    {
        self::init();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
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

    public static function csrfToken(): string
    {
        self::init();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrf(?string $token): bool
    {
        self::init();
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if (!is_string($token) || $token === '' || $sessionToken === '') {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }
}
