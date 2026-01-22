<?php

class Database
{
    private \PDO $pdo;

    public function __construct()
    {
        $dsn = getenv('PHONETAL_DSN') ?: 'mysql:host=localhost;dbname=phonetal;charset=utf8mb4';
        $user = getenv('PHONETAL_DB_USER') ?: 'root';
        $password = getenv('PHONETAL_DB_PASS') ?: '';
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ];

        $this->pdo = new \PDO($dsn, $user, $password, $options);
    }

    public function fetchAvailableDevices(): array
    {
        $statement = $this->pdo->query(
            "SELECT id, znacka, model FROM MA_zariadenia WHERE stav = 'dostupne' ORDER BY znacka, model"
        );

        $rows = $statement->fetchAll();
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
        $statement = $this->pdo->prepare(
            "SELECT DISTINCT CONCAT(znacka, ' ', model) AS label
             FROM MA_zariadenia
             WHERE znacka LIKE :q OR model LIKE :q
             ORDER BY znacka, model
             LIMIT 8"
        );

        $statement->execute(['q' => '%' . $query . '%']);
        return $statement->fetchAll(\PDO::FETCH_COLUMN);
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
            $conditions[] = $this->buildInClause('typ_zariadenia', 'typy', $filters['typy'], $params);
        }
        if (!empty($filters['znacky'])) {
            $conditions[] = $this->buildInClause('znacka', 'znacky', $filters['znacky'], $params);
        }
        if (!empty($filters['ram'])) {
            $conditions[] = $this->buildInClause('ram', 'ram', $filters['ram'], $params);
        }
        if (!empty($filters['uhlopriecky'])) {
            $conditions[] = $this->buildInClause('velkost_displeja', 'uhlopriecky', $filters['uhlopriecky'], $params);
        }

        $sql = "SELECT znacka, model, ram, velkost_displeja, cena_za_den
                FROM MA_zariadenia
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY znacka, model";

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        $rows = $statement->fetchAll();

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

    private function fetchDistinctOptions(string $column): array
    {
        $statement = $this->pdo->query(
            "SELECT DISTINCT {$column} FROM MA_zariadenia WHERE {$column} IS NOT NULL ORDER BY {$column}"
        );
        return array_values(array_filter($statement->fetchAll(\PDO::FETCH_COLUMN)));
    }

    private function buildInClause(string $column, string $prefix, array $values, array &$params): string
    {
        $placeholders = [];
        foreach (array_values($values) as $index => $value) {
            $name = $prefix . $index;      // bez :
            $placeholders[] = ':' . $name; // v SQL s :
            $params[$name] = $value;       // v execute bez :
        }
        return sprintf('%s IN (%s)', $column, implode(', ', $placeholders));
    }
}