<?php

class Database
{
    private string $host = "mysql80.r2.websupport.sk";
    private string $user = "sirgel";
    private string $password = "Rh3.0x(n}@";
    private string $dbname = "sirgel";

    private \mysqli $conn;

    public function __construct()
    {
        $this->conn = new \mysqli(
            $this->host,
            $this->user,
            $this->password,
            $this->dbname
        );

        if ($this->conn->connect_error) {
            die("Chyba pripojenia: " . $this->conn->connect_error);
        }

        $this->conn->set_charset("utf8mb4");
    }

    public function fetchAvailableDevices(): array
    {
        $rows = $this->fetchAll(
            "SELECT id, znacka, model FROM MA_zariadenia WHERE stav = 'dostupne' ORDER BY znacka, model"
        );
        $devices = [];

        foreach ($rows as $row) {
            $devices[] = [
                'value' => (int) $row['id'],
                'label' => $row['znacka'] . ' ' . $row['model'],
            ];
        }

        return $devices;
    }

    public function fetchSearchSuggestions(string $query): array
    {
        $sql =
            "SELECT id, CONCAT(znacka, ' ', model) AS label
             FROM MA_zariadenia
             WHERE CONCAT(znacka, ' ', model) COLLATE utf8_general_ci LIKE ?
             ORDER BY label
             LIMIT 8";
        $rows = $this->fetchAll($sql, ['%' . $query . '%']);
        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'label' => $row['label'],
            ];
        }, $rows);
    }

    public function fetchDeviceCount(): int
    {
        $rows = $this->fetchAll("SELECT COUNT(*) AS cnt FROM MA_zariadenia");
        return (int) ($rows[0]['cnt'] ?? 0);
    }

    public function fetchDeviceSample(int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $rows = $this->fetchAll(
            "SELECT znacka, model FROM MA_zariadenia ORDER BY id DESC LIMIT {$limit}"
        );
        return array_map(
            static fn(array $row): string => trim(($row['znacka'] ?? '') . ' ' . ($row['model'] ?? '')),
            $rows
        );
    }

    public function fetchSearchDiagnostics(string $query): array
    {
        $pattern = '%' . $query . '%';
        $sql = "SELECT
                    (SELECT COUNT(*) FROM MA_zariadenia) AS total,
                    (SELECT COUNT(*) FROM MA_zariadenia WHERE CONCAT(znacka, ' ', model) LIKE ?) AS match_concat,
                    (SELECT COUNT(*) FROM MA_zariadenia WHERE znacka LIKE ?) AS match_brand,
                    (SELECT COUNT(*) FROM MA_zariadenia WHERE model LIKE ?) AS match_model,
                    (SELECT COUNT(*) FROM MA_zariadenia WHERE CONCAT(znacka, ' ', model) COLLATE utf8_general_ci LIKE ?) AS match_collate";
        $rows = $this->fetchAll($sql, [$pattern, $pattern, $pattern, $pattern]);
        return $rows[0] ?? [];
    }

    public function fetchFilterOptions(): array
    {
        return [
            'typy' => $this->fetchDistinctOptions('typ_zariadenia'),
            'znacky' => $this->fetchDistinctOptions('znacka'),
            'ram' => $this->fetchDistinctOptions('ram'),
            'uhlopriecky' => $this->fetchDistinctOptions('velkost_displeja'),
            'stavy' => ['dostupne', 'nedostupne'],
        ];
    }

    public function fetchDevicesByFilters(array $filters): array
    {
        $conditions = [];
        $params = [];

        $rawStatus = $filters['stav'] ?? null;
        if (is_array($rawStatus)) {
            $rawStatus = $rawStatus[0] ?? null;
        }
        if ($rawStatus === null && isset($filters['stavy']) && is_array($filters['stavy'])) {
            $rawStatus = $filters['stavy'][0] ?? null;
        }
        $status = strtolower(trim((string) ($rawStatus ?? 'dostupne')));
        if (!in_array($status, ['dostupne', 'nedostupne'], true)) {
            $status = 'dostupne';
        }
        $conditions[] = 'stav = ?';
        $params[] = $status;

        if (!empty($filters['typy'])) {
            $conditions[] = $this->buildInClause('typ_zariadenia', $filters['typy'], $params);
        }
        if (!empty($filters['znacky'])) {
            $conditions[] = $this->buildInClause('znacka', $filters['znacky'], $params);
        }
        if (!empty($filters['ram'])) {
            $conditions[] = $this->buildInClause('ram', $filters['ram'], $params);
        }
        if (!empty($filters['uhlopriecky'])) {
            $conditions[] = $this->buildInClause('velkost_displeja', $filters['uhlopriecky'], $params);
        }

        $sql = "SELECT id, znacka, model, ram, velkost_displeja, cena_za_den
                FROM MA_zariadenia
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY znacka, model";

        $rows = $this->fetchAll($sql, $params);

        $devices = [];
        foreach ($rows as $row) {
            $details = [];
            if (!empty($row['ram'])) $details[] = $row['ram'] . ' GB RAM';
            if (!empty($row['velkost_displeja'])) $details[] = $row['velkost_displeja'];

            $devices[] = [
                'id' => (int) $row['id'],
                'label' => $row['znacka'] . ' ' . $row['model'],
                'name' => $row['znacka'] . ' ' . $row['model'],
                'details' => $details ? implode(' • ', $details) : 'Parametre budú doplnené.',
                'price' => 'od ' . number_format((float) $row['cena_za_den'], 2, ',', ' ') . ' €/deň',
            ];
        }

        return $devices;
    }

    public function fetchDeviceById(int $deviceId): ?array
    {
        $sql = "SELECT id, znacka, model, typ_zariadenia, velkost_displeja, ram, pamat, rok_vydania, softver, cena_za_den, zaloha, popis, stav
                FROM MA_zariadenia
                WHERE id = ?
                LIMIT 1";
        $rows = $this->fetchAll($sql, [$deviceId]);

        if ($rows === []) {
            return null;
        }

        $row = $rows[0];
        $details = [];
        if (!empty($row['ram'])) $details[] = $row['ram'] . ' GB RAM';
        if (!empty($row['velkost_displeja'])) $details[] = $row['velkost_displeja'];

        return [
            'id' => (int) $row['id'],
            'brand' => $row['znacka'],
            'model' => $row['model'],
            'type' => $row['typ_zariadenia'] ?: 'zariadenie',
            'ram' => $row['ram'],
            'display' => $row['velkost_displeja'],
            'memory' => $row['pamat'],
            'release_year' => $row['rok_vydania'],
            'software' => $row['softver'],
            'deposit' => $row['zaloha'],
            'description' => $row['popis'],
            'name' => $row['znacka'] . ' ' . $row['model'],
            'details' => $details ? implode(' • ', $details) : 'Parametre budú doplnené.',
            'price_per_day' => (float) $row['cena_za_den'],
            'price' => 'od ' . number_format((float) $row['cena_za_den'], 2, ',', ' ') . ' €/deň',
            'status' => $row['stav'] ?? 'dostupne',
        ];
    }

    public function updateDevice(int $deviceId, array $payload): void
    {
        $sql = "UPDATE MA_zariadenia
                SET znacka = ?,
                    model = ?,
                    typ_zariadenia = ?,
                    velkost_displeja = ?,
                    ram = ?,
                    pamat = ?,
                    rok_vydania = ?,
                    softver = ?,
                    cena_za_den = ?,
                    zaloha = ?,
                    popis = ?,
                    stav = ?
                WHERE id = ?
                LIMIT 1";
        $params = [
            $payload['znacka'],
            $payload['model'],
            $payload['typ_zariadenia'],
            $payload['velkost_displeja'],
            $payload['ram'],
            $payload['pamat'],
            $payload['rok_vydania'],
            $payload['softver'],
            $payload['cena_za_den'],
            $payload['zaloha'],
            $payload['popis'],
            $payload['stav'],
            $deviceId,
        ];

        $statement = $this->conn->prepare($sql);
        if ($statement === false) {
            throw new \RuntimeException('SQL chyba: ' . $this->conn->error);
        }

        $types = $this->buildParamTypes($params);
        $bindParams = [$types];
        foreach ($params as $index => $value) {
            $bindParams[] = &$params[$index];
        }
        $statement->bind_param(...$bindParams);
        $statement->execute();

        if ($statement->errno) {
            $error = $statement->error;
            $statement->close();
            throw new \RuntimeException('SQL chyba: ' . $error);
        }

        $statement->close();
    }

    public function fetchReviewsByDeviceId(int $deviceId): array
    {
        $sql = "SELECT r.hodnotenie,
                       r.komentar,
                       r.datum,
                       CONCAT(u.meno, ' ', u.priezvisko) AS autor
                FROM MA_recenzie r
                JOIN MA_pouzivatelia u ON u.id = r.pouzivatel_id
                WHERE r.zariadenie_id = ?
                ORDER BY r.datum DESC";
        $rows = $this->fetchAll($sql, [$deviceId]);

        return array_map(static function (array $row): array {
            return [
                'rating' => (int) $row['hodnotenie'],
                'text' => $row['komentar'],
                'author' => $row['autor'],
                'date' => $row['datum'],
            ];
        }, $rows);
    }

    public function createReview(int $userId, int $deviceId, int $rating, string $comment): void
    {
        $sql = "INSERT INTO MA_recenzie (pouzivatel_id, zariadenie_id, hodnotenie, komentar)
                VALUES (?, ?, ?, ?)";
        $statement = $this->conn->prepare($sql);
        if ($statement === false) {
            throw new \RuntimeException('SQL chyba: ' . $this->conn->error);
        }

        $statement->bind_param('iiis', $userId, $deviceId, $rating, $comment);
        $statement->execute();

        if ($statement->errno) {
            $error = $statement->error;
            $statement->close();
            throw new \RuntimeException('SQL chyba: ' . $error);
        }

        $statement->close();
    }

    public function fetchRentEndNotifications(array $daysIntervals): array
    {
        if ($daysIntervals === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($daysIntervals), '?'));
        $sql = "SELECT p.id AS prenajom_id,
                       p.koniec,
                       u.email,
                       CONCAT(u.meno, ' ', u.priezvisko) AS meno,
                       DATEDIFF(p.koniec, CURDATE()) AS dni
                FROM MA_prenajmy p
                JOIN MA_pouzivatelia u ON u.id = p.pouzivatel_id
                WHERE p.stav = 'aktivny'
                  AND DATEDIFF(p.koniec, CURDATE()) IN ({$placeholders})";

        return $this->fetchAll($sql, array_values($daysIntervals));
    }

    public function fetchUserByEmail(string $email): ?array
    {
        $rows = $this->fetchAll(
            "SELECT id, meno, priezvisko, email, password_hash, telefon, rodne_cislo, mesto, ulica, psc, iban, bic, meno_uctu, rola
             FROM MA_pouzivatelia
             WHERE email = ?
             LIMIT 1",
            [$email]
        );

        return $rows[0] ?? null;
    }

    public function fetchUsers(): array
    {
        return $this->fetchAll(
            "SELECT id, meno, priezvisko, email, telefon, mesto, rola
             FROM MA_pouzivatelia
             ORDER BY priezvisko, meno"
        );
    }

    public function fetchUserRentals(int $userId): array
    {
        $rows = $this->fetchAll(
            "SELECT id, zaciatok, koniec, celkova_cena, stav
             FROM MA_prenajmy
             WHERE pouzivatel_id = ?
             ORDER BY zaciatok DESC, id DESC",
            [$userId]
        );

        $today = new \DateTimeImmutable('today');

        return array_map(static function (array $row) use ($today): array {
            $startDate = \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($row['zaciatok'] ?? '')) ?: $today;
            $endDate = \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($row['koniec'] ?? '')) ?: $today;
            $status = (string) ($row['stav'] ?? 'aktivny');

            if ($status === 'zruseny') {
                $timelineLabel = 'Objednávka bola zrušená.';
            } elseif ($status === 'ukonceny' || $endDate < $today) {
                $timelineLabel = 'Prenájom je ukončený.';
            } elseif ($startDate > $today) {
                $timelineLabel = 'Začiatok prenájmu: ' . $startDate->format('d.m.Y');
            } else {
                $daysRemaining = (int) $today->diff($endDate)->format('%r%a');
                $timelineLabel = $daysRemaining >= 0
                    ? 'Do ukončenia ostáva ' . $daysRemaining . ' dní.'
                    : 'Prenájom čaká na ukončenie.';
            }

            return [
                'id' => (int) ($row['id'] ?? 0),
                'start_date_raw' => $startDate->format('Y-m-d'),
                'end_date_raw' => $endDate->format('Y-m-d'),
                'date_range' => $startDate->format('d.m.Y') . ' – ' . $endDate->format('d.m.Y'),
                'total_price_raw' => (float) ($row['celkova_cena'] ?? 0),
                'total_price' => number_format((float) ($row['celkova_cena'] ?? 0), 2, ',', ' ') . ' €',
                'status' => $status,
                'timeline_label' => $timelineLabel,
            ];
        }, $rows);
    }

    public function createDevice(array $payload): int
    {
        $sql = "INSERT INTO MA_zariadenia
                (znacka, model, typ_zariadenia, velkost_displeja, ram, pamat, rok_vydania, softver, cena_za_den, zaloha, popis, stav)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [
            $payload['znacka'],
            $payload['model'],
            $payload['typ_zariadenia'],
            $payload['velkost_displeja'],
            $payload['ram'],
            $payload['pamat'],
            $payload['rok_vydania'],
            $payload['softver'],
            $payload['cena_za_den'],
            $payload['zaloha'],
            $payload['popis'],
            $payload['stav'],
        ];

        $statement = $this->conn->prepare($sql);
        if ($statement === false) {
            throw new \RuntimeException('SQL chyba: ' . $this->conn->error);
        }

        $types = $this->buildParamTypes($params);
        $bindParams = [$types];
        foreach ($params as $index => $value) {
            $bindParams[] = &$params[$index];
        }
        $statement->bind_param(...$bindParams);
        $statement->execute();

        if ($statement->errno) {
            $error = $statement->error;
            $statement->close();
            throw new \RuntimeException('SQL chyba: ' . $error);
        }

        $insertId = $statement->insert_id;
        $statement->close();

        return $insertId;
    }

    public function createDeviceImages(int $deviceId, array $paths): void
    {
        if ($paths === []) {
            return;
        }

        $statement = $this->conn->prepare(
            "INSERT INTO MA_obrazky (zariadenie_id, cesta_k_suboru) VALUES (?, ?)"
        );
        if ($statement === false) {
            throw new \RuntimeException('SQL chyba: ' . $this->conn->error);
        }

        foreach ($paths as $path) {
            $statement->bind_param('is', $deviceId, $path);
            $statement->execute();
            if ($statement->errno) {
                $error = $statement->error;
                $statement->close();
                throw new \RuntimeException('SQL chyba: ' . $error);
            }
        }

        $statement->close();
    }

    public function fetchDeviceImages(int $deviceId): array
    {
        $rows = $this->fetchAll(
            "SELECT cesta_k_suboru FROM MA_obrazky WHERE zariadenie_id = ? ORDER BY id",
            [$deviceId]
        );
        $paths = [];
        foreach (array_column($rows, 'cesta_k_suboru') as $rawPath) {
            if (!is_string($rawPath) || trim($rawPath) === '') {
                continue;
            }
            $normalized = $this->normalizeImagePath($rawPath);
            if ($normalized !== null) {
                $paths[] = $normalized;
            }
        }
        return array_values(array_unique($paths));
    }

    private function normalizeImagePath(string $rawPath): ?string
    {
        $path = trim(str_replace('\\', '/', $rawPath));
        if ($path === '') {
            return null;
        }

        if (preg_match('#^(https?://|data:)#i', $path) === 1) {
            return $path;
        }

        if (preg_match('#^[A-Za-z]:/#', $path) === 1) {
            $path = 'pictures/' . basename($path);
        }

        $path = preg_replace('#^\./+#', '', $path);
        if ($path === null || $path === '') {
            return null;
        }

        if (strpos($path, 'pictures/') !== 0 && strpos($path, '/') === false) {
            $path = 'pictures/' . $path;
        }

        return $path;
    }

    public function createUser(array $payload): int
    {
        $sql = "INSERT INTO MA_pouzivatelia
                (meno, priezvisko, email, password_hash, telefon, rodne_cislo, mesto, ulica, psc, iban, bic, meno_uctu, rola)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [
            $payload['meno'],
            $payload['priezvisko'],
            $payload['email'],
            $payload['password_hash'],
            $payload['telefon'],
            $payload['rodne_cislo'],
            $payload['mesto'],
            $payload['ulica'],
            $payload['psc'],
            $payload['iban'],
            $payload['bic'],
            $payload['meno_uctu'],
            $payload['rola'],
        ];

        $statement = $this->conn->prepare($sql);
        if ($statement === false) {
            throw new \RuntimeException('SQL chyba: ' . $this->conn->error);
        }

        $types = $this->buildParamTypes($params);
        $bindParams = [$types];
        foreach ($params as $index => $value) {
            $bindParams[] = &$params[$index];
        }
        $statement->bind_param(...$bindParams);
        $statement->execute();

        if ($statement->errno) {
            $error = $statement->error;
            $statement->close();
            throw new \RuntimeException('SQL chyba: ' . $error);
        }

        $insertId = $statement->insert_id;
        $statement->close();

        return $insertId;
    }

    public function createRental(int $userId, string $startDate, string $endDate, float $totalPrice, array $items): int
    {
        if ($items === []) {
            throw new \InvalidArgumentException('Prenajom musi obsahovat aspon jednu polozku.');
        }

        $this->conn->begin_transaction();
        try {
            $sql = "INSERT INTO MA_prenajmy (pouzivatel_id, zaciatok, koniec, celkova_cena)
                    VALUES (?, ?, ?, ?)";
            $statement = $this->conn->prepare($sql);
            if ($statement === false) {
                throw new \RuntimeException('SQL chyba: ' . $this->conn->error);
            }

            $statement->bind_param('issd', $userId, $startDate, $endDate, $totalPrice);
            $statement->execute();

            if ($statement->errno) {
                $error = $statement->error;
                $statement->close();
                throw new \RuntimeException('SQL chyba: ' . $error);
            }

            $rentalId = $statement->insert_id;
            $statement->close();

            $this->updateDeviceAvailability(array_column($items, 'device_id'), 'nedostupne');

            $this->conn->commit();
            return $rentalId;
        } catch (Throwable $exception) {
            $this->conn->rollback();
            throw $exception;
        }
    }

    public function updateUserFields(int $userId, array $fields): void
    {
        if ($fields === []) {
            return;
        }

        $setParts = [];
        $params = [];
        foreach ($fields as $column => $value) {
            $setParts[] = "{$column} = ?";
            $params[] = $value;
        }
        $params[] = $userId;

        $sql = "UPDATE MA_pouzivatelia SET " . implode(', ', $setParts) . " WHERE id = ?";
        $statement = $this->conn->prepare($sql);
        if ($statement === false) {
            throw new \RuntimeException('SQL chyba: ' . $this->conn->error);
        }

        $types = $this->buildParamTypes($params);
        $bindParams = [$types];
        foreach ($params as $index => $value) {
            $bindParams[] = &$params[$index];
        }
        $statement->bind_param(...$bindParams);
        $statement->execute();

        if ($statement->errno) {
            $error = $statement->error;
            $statement->close();
            throw new \RuntimeException('SQL chyba: ' . $error);
        }

        $statement->close();
    }

    private function updateDeviceAvailability(array $deviceIds, string $status): void
    {
        $deviceIds = array_values(array_unique(array_map('intval', $deviceIds)));
        $deviceIds = array_values(array_filter($deviceIds, static fn(int $id): bool => $id > 0));
        if ($deviceIds === []) {
            return;
        }

        $params = [$status];
        $clause = $this->buildInClause('id', $deviceIds, $params);
        $sql = "UPDATE MA_zariadenia SET stav = ? WHERE {$clause}";
        $statement = $this->conn->prepare($sql);
        if ($statement === false) {
            throw new \RuntimeException('SQL chyba: ' . $this->conn->error);
        }

        $types = $this->buildParamTypes($params);
        $bindParams = [$types];
        foreach ($params as $index => $value) {
            $bindParams[] = &$params[$index];
        }
        $statement->bind_param(...$bindParams);
        $statement->execute();

        if ($statement->errno) {
            $error = $statement->error;
            $statement->close();
            throw new \RuntimeException('SQL chyba: ' . $error);
        }

        $statement->close();
    }

    private function fetchDistinctOptions(string $column): array
    {
        $rows = $this->fetchAll(
            "SELECT DISTINCT {$column} FROM MA_zariadenia WHERE {$column} IS NOT NULL ORDER BY {$column}"
        );
        return array_values(array_filter(array_column($rows, $column)));
    }

    private function buildInClause(string $column, array $values, array &$params): string
    {
        $placeholders = array_fill(0, count($values), '?');
        foreach (array_values($values) as $value) {
            $params[] = $value;
        }
        return sprintf('%s IN (%s)', $column, implode(', ', $placeholders));
    }

    private function fetchAll(string $sql, array $params = []): array
    {
        if ($params === []) {
            $result = $this->conn->query($sql);
            if ($result === false) {
                throw new \RuntimeException('SQL chyba: ' . $this->conn->error);
            }
            return $result->fetch_all(MYSQLI_ASSOC);
        }

        $statement = $this->conn->prepare($sql);
        if ($statement === false) {
            throw new \RuntimeException('SQL chyba: ' . $this->conn->error);
        }

        $types = $this->buildParamTypes($params);
        $bindParams = [$types];
        foreach ($params as $index => $value) {
            $bindParams[] = &$params[$index];
        }
        $statement->bind_param(...$bindParams);
        $statement->execute();

        $rows = [];
        if (method_exists($statement, 'get_result')) {
            $result = $statement->get_result();
            if ($result !== false) {
                $rows = $result->fetch_all(MYSQLI_ASSOC);
                $statement->close();
                return $rows;
            }
        }

        $metadata = $statement->result_metadata();
        if ($metadata) {
            $fields = [];
            while ($field = $metadata->fetch_field()) {
                $fields[] = $field->name;
            }
            $metadata->free();

            if ($fields !== []) {
                $rowData = [];
                $bindResult = [];
                foreach ($fields as $fieldName) {
                    $rowData[$fieldName] = null;
                    $bindResult[] = &$rowData[$fieldName];
                }

                $statement->bind_result(...$bindResult);
                while ($statement->fetch()) {
                    $row = [];
                    foreach ($fields as $fieldName) {
                        $row[$fieldName] = $rowData[$fieldName];
                    }
                    $rows[] = $row;
                }
            }
        }
        $statement->close();

        return $rows;
    }

    private function buildParamTypes(array $params): string
    {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        return $types;
    }
}
