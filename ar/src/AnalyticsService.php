<?php

declare(strict_types=1);

final class AnalyticsService
{
    /**
     * @param array<int, array<string, mixed>> $transactions
     * @return array<int, int>
     */
    public function getAvailableYears(array $transactions): array
    {
        return $this->collectAvailableYears($transactions);
    }

    /**
     * @param array<int, int> $availableYears
     */
    public function getResolvedYear(?int $year, array $availableYears): ?int
    {
        return $this->resolveYearFilter($year, $availableYears);
    }

    /**
     * @param array<int, array<string, mixed>> $transactions
     * @param array<int, array<string, mixed>> $budgetLines
     * @param array<int, array<string, mixed>> $inventoryItems
     * @param array<string, string> $categoryMap
     * @return array<string, mixed>
     */
    public function buildDashboardData(
        array $transactions,
        array $budgetLines,
        array $inventoryItems,
        array $categoryMap,
        ?int $year
    ): array {
        $todayIso = (new DateTimeImmutable('today'))->format('Y-m-d');
        $dashboardTransactions = array_values(array_filter(
            $transactions,
            fn(array $row): bool => !$this->isFutureBookingDate((string)($row['booking_date'] ?? ''), $todayIso)
        ));

        $availableYears = $this->collectAvailableYears($dashboardTransactions);
        $effectiveYear = $this->resolveYearFilter($year, $availableYears);

        $filteredTransactions = array_values(array_filter(
            $dashboardTransactions,
            fn(array $row): bool => $this->yearFromDate((string)($row['booking_date'] ?? '')) === $effectiveYear
        ));
        $filteredBudget = array_values(array_filter(
            $budgetLines,
            fn(array $row): bool => $this->yearFromDate((string)($row['period_date'] ?? '')) === $effectiveYear
        ));

        $sumIn = 0.0;
        $sumOut = 0.0;
        foreach ($filteredTransactions as $row) {
            $sumIn += (float)($row['amount_in'] ?? 0);
            $sumOut += (float)($row['amount_out'] ?? 0);
        }

        $net = $sumIn - $sumOut;

        $budgetNet = 0.0;
        foreach ($filteredBudget as $row) {
            $budgetNet += (float)($row['amount'] ?? 0);
        }

        $months = $this->buildMonthlySeries($filteredTransactions);
        $categoryTotals = $this->buildCategoryTotals($filteredTransactions, $categoryMap);
        $inventoryStats = $this->buildInventoryStats($inventoryItems);

        return [
            'availableYears' => $availableYears,
            'effectiveYear' => $effectiveYear,
            'kpis' => [
                'income' => $sumIn,
                'expense' => $sumOut,
                'net' => $net,
                'budgetNet' => $budgetNet,
                'budgetGap' => $net - $budgetNet,
                'transactionCount' => count($filteredTransactions),
            ],
            'monthlySeries' => $months,
            'categoryTotals' => $categoryTotals,
            'inventoryStats' => $inventoryStats,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $transactions
     * @param array<int, array<string, mixed>> $budgetLines
     * @param array<string, string> $categoryMap
     * @return array<string, mixed>
     */
    public function buildReportData(
        array $transactions,
        array $budgetLines,
        array $categoryMap,
        ?int $year
    ): array {
        $availableYears = $this->collectAvailableYears($transactions);
        $effectiveYear = $this->resolveYearFilter($year, $availableYears);

        $filteredTransactions = array_values(array_filter(
            $transactions,
            fn(array $row): bool => $this->yearFromDate((string)($row['booking_date'] ?? '')) === $effectiveYear
        ));
        $filteredBudget = array_values(array_filter(
            $budgetLines,
            fn(array $row): bool => $this->yearFromDate((string)($row['period_date'] ?? '')) === $effectiveYear
        ));

        $actualByCategory = [];
        foreach ($filteredTransactions as $row) {
            $code = trim((string)($row['cost_category_code'] ?? ''));
            $name = $categoryMap[$code] ?? ($row['cost_category_name_raw'] ?? 'Unmapped');
            $name = trim((string)$name);
            if ($name === '') {
                $name = 'Unmapped';
            }
            if (!isset($actualByCategory[$name])) {
                $actualByCategory[$name] = 0.0;
            }
            $actualByCategory[$name] += (float)($row['amount_in'] ?? 0) - (float)($row['amount_out'] ?? 0);
        }

        $budgetByCategory = [];
        foreach ($filteredBudget as $row) {
            $code = trim((string)($row['cost_category_code'] ?? ''));
            $name = $categoryMap[$code] ?? ('Code ' . $code);
            if (!isset($budgetByCategory[$name])) {
                $budgetByCategory[$name] = 0.0;
            }
            $budgetByCategory[$name] += (float)($row['amount'] ?? 0);
        }

        $categoryNames = array_unique(array_merge(array_keys($actualByCategory), array_keys($budgetByCategory)));
        sort($categoryNames);

        $rows = [];
        $sumActual = 0.0;
        $sumBudget = 0.0;
        foreach ($categoryNames as $name) {
            $actual = $actualByCategory[$name] ?? 0.0;
            $budget = $budgetByCategory[$name] ?? 0.0;
            $sumActual += $actual;
            $sumBudget += $budget;
            $rows[] = [
                'category' => $name,
                'actual' => $actual,
                'budget' => $budget,
                'gap' => $actual - $budget,
            ];
        }

        $monthlyActual = array_fill(1, 12, 0.0);
        foreach ($filteredTransactions as $row) {
            $date = (string)($row['booking_date'] ?? '');
            if (strlen($date) < 7) {
                continue;
            }
            $month = (int)substr($date, 5, 2);
            if ($month >= 1 && $month <= 12) {
                $monthlyActual[$month] += (float)($row['amount_in'] ?? 0) - (float)($row['amount_out'] ?? 0);
            }
        }

        $monthlyBudget = array_fill(1, 12, 0.0);
        foreach ($filteredBudget as $row) {
            $date = (string)($row['period_date'] ?? '');
            if (strlen($date) < 7) {
                continue;
            }
            $month = (int)substr($date, 5, 2);
            if ($month >= 1 && $month <= 12) {
                $monthlyBudget[$month] += (float)($row['amount'] ?? 0);
            }
        }

        return [
            'availableYears' => $availableYears,
            'effectiveYear' => $effectiveYear,
            'categoryRows' => $rows,
            'totals' => [
                'actual' => $sumActual,
                'budget' => $sumBudget,
                'gap' => $sumActual - $sumBudget,
            ],
            'monthlySeries' => [
                'labels' => array_map(static fn(int $m): string => str_pad((string)$m, 2, '0', STR_PAD_LEFT), range(1, 12)),
                'actual' => array_values($monthlyActual),
                'budget' => array_values($monthlyBudget),
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $transactions
     * @return array<int, int>
     */
    private function collectAvailableYears(array $transactions): array
    {
        $years = [];
        foreach ($transactions as $row) {
            $year = $this->yearFromDate((string)($row['booking_date'] ?? ''));
            if ($year !== null) {
                $years[$year] = true;
            }
        }
        $list = array_keys($years);
        sort($list);
        return $list;
    }

    /**
     * @param array<int, int> $availableYears
     */
    private function resolveYearFilter(?int $year, array $availableYears): ?int
    {
        if ($year !== null && in_array($year, $availableYears, true)) {
            return $year;
        }
        if (empty($availableYears)) {
            return null;
        }
        return max($availableYears);
    }

    private function yearFromDate(string $raw): ?int
    {
        if (strlen($raw) < 4) {
            return null;
        }
        $prefix = substr($raw, 0, 4);
        if (!ctype_digit($prefix)) {
            return null;
        }
        return (int)$prefix;
    }

    private function isFutureBookingDate(string $rawDate, string $todayIso): bool
    {
        $date = trim($rawDate);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        return $date > $todayIso;
    }

    /**
     * @param array<int, array<string, mixed>> $transactions
     * @return array<string, array<int, float>>
     */
    private function buildMonthlySeries(array $transactions): array
    {
        $labels = [];
        $income = [];
        $expense = [];
        $net = [];
        for ($i = 1; $i <= 12; $i++) {
            $key = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
            $labels[] = $key;
            $income[$key] = 0.0;
            $expense[$key] = 0.0;
        }

        foreach ($transactions as $row) {
            $date = (string)($row['booking_date'] ?? '');
            if (strlen($date) < 7) {
                continue;
            }
            $month = substr($date, 5, 2);
            if (!isset($income[$month])) {
                continue;
            }
            $income[$month] += (float)($row['amount_in'] ?? 0);
            $expense[$month] += (float)($row['amount_out'] ?? 0);
        }

        foreach ($labels as $month) {
            $net[] = $income[$month] - $expense[$month];
        }

        return [
            'labels' => $labels,
            'income' => array_values($income),
            'expense' => array_values($expense),
            'net' => $net,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $transactions
     * @param array<string, string> $categoryMap
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    private function buildCategoryTotals(array $transactions, array $categoryMap): array
    {
        $totals = [];
        foreach ($transactions as $row) {
            $code = trim((string)($row['cost_category_code'] ?? ''));
            $name = $categoryMap[$code] ?? ($row['cost_category_name_raw'] ?? 'Unmapped');
            $name = trim((string)$name);
            if ($name === '') {
                $name = 'Unmapped';
            }
            $net = (float)($row['amount_in'] ?? 0) - (float)($row['amount_out'] ?? 0);
            if (!isset($totals[$name])) {
                $totals[$name] = 0.0;
            }
            $totals[$name] += $net;
        }

        arsort($totals);
        $labels = array_keys($totals);
        $values = array_values($totals);
        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @param array<int, array<string, mixed>> $inventoryItems
     * @return array<string, float|int>
     */
    private function buildInventoryStats(array $inventoryItems): array
    {
        $purchaseValue = 0.0;
        $currentValue = 0.0;
        $disposalValue = 0.0;
        foreach ($inventoryItems as $item) {
            $qty = (float)($item['quantity'] ?? 0);
            $purchaseValue += ((float)($item['purchase_price_unit'] ?? 0)) * $qty;
            $currentValue += (float)($item['current_value'] ?? 0);
            $disposalValue += (float)($item['disposal_value'] ?? 0);
        }

        return [
            'itemCount' => count($inventoryItems),
            'purchaseValue' => $purchaseValue,
            'currentValue' => $currentValue,
            'disposalValue' => $disposalValue,
        ];
    }
}

