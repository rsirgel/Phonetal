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
            "SELECT DISTINCT CONCAT(znacka, ' ', model) AS label
             FROM MA_zariadenia
             WHERE znacka LIKE ? OR model LIKE ?
             ORDER BY znacka, model
             LIMIT 8";
        $rows = $this->fetchAll($sql, ['%' . $query . '%', '%' . $query . '%']);
        return array_column($rows, 'label');
    }

    public function fetchFilterOptions(): array
    {
        return [
            'typy' => $this->fetchDistinctOptions('typ_zariadenia'),
            'znacky' => $this->fetchDistinctOptions('znacka'),
            'ram' => $this->fetchDistinctOptions('ram'),
            'uhlopriecky' => $this->fetchDistinctOptions('velkost_displeja'),
        ];
    }

    public function fetchDevicesByFilters(array $filters): array
    {
        $conditions = ["stav = 'dostupne'"];
        $params = [];

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

        $sql = "SELECT znacka, model, ram, velkost_displeja, cena_za_den
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
                'label' => $row['znacka'] . ' ' . $row['model'],
                'name' => $row['znacka'] . ' ' . $row['model'],
                'details' => $details ? implode(' • ', $details) : 'Parametre budú doplnené.',
                'price' => 'od ' . number_format((float) $row['cena_za_den'], 2, ',', ' ') . ' €/deň',
            ];
        }

        return $devices;
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

        $result = $statement->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
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
