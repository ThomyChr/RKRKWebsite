<?php

declare(strict_types=1);

final class DataRepository
{
    private string $dataDir;
    private ?YearlyStore $yearly = null;

    public function __construct(string $dataDir)
    {
        $this->dataDir = rtrim($dataDir, DIRECTORY_SEPARATOR);
    }

    public function yearly(): YearlyStore
    {
        if ($this->yearly === null) {
            require_once __DIR__ . '/JsonStore.php';
            require_once __DIR__ . '/YearlyStore.php';
            $this->yearly = new YearlyStore($this->dataDir, new JsonStore($this->dataDir));
            $this->yearly->ensureMigrated();
        }

        return $this->yearly;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTransactions(?int $year = null): array
    {
        if ($year !== null) {
            return $this->yearly()->readYear((string)$year);
        }

        return $this->yearly()->readAll();
    }

    /**
     * @return array<int, int>
     */
    public function getAvailableTransactionYears(): array
    {
        return $this->yearly()->availableYears();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getBudgetLines(): array
    {
        return $this->readJsonList('budget_plan_lines.json');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getInventoryItems(): array
    {
        return $this->readJsonList('inventory_items.json');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAccounts(): array
    {
        return $this->readJsonList('accounts.json');
    }

    /**
     * @return array<string, string>
     */
    public function getCategoryMapByCode(): array
    {
        $rows = $this->readJsonList('cost_categories.json');
        $map = [];
        foreach ($rows as $row) {
            $code = trim((string)($row['code'] ?? ''));
            $name = trim((string)($row['name'] ?? ''));
            if ($code === '' || $name === '' || $code === 'KA#') {
                continue;
            }
            if (isset($row['is_active']) && $row['is_active'] === false) {
                continue;
            }
            $map[$code] = $name;
        }
        return $map;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readJsonList(string $fileName): array
    {
        $path = $this->dataDir . DIRECTORY_SEPARATOR . $fileName;
        if (!is_file($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, static fn($item): bool => is_array($item)));
    }
}

