<?php

declare(strict_types=1);

final class DataRepository
{
    private string $dataDir;

    public function __construct(string $dataDir)
    {
        $this->dataDir = rtrim($dataDir, DIRECTORY_SEPARATOR);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTransactions(): array
    {
        return $this->readJsonList('transactions.json');
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

