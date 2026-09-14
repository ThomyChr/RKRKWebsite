<?php

declare(strict_types=1);

/**
 * Kaufdatum aus Excel-Format YYYY.MM oder YYYY.M parsen.
 */
function inventur_parse_purchase_period(string $raw): ?DateTimeImmutable
{
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }
    if (preg_match('/^(\d{4})\.(\d{1,2})$/', $raw, $m)) {
        $y = (int)$m[1];
        $mo = min(12, max(1, (int)$m[2]));

        return new DateTimeImmutable(sprintf('%04d-%02d-01', $y, $mo));
    }
    return null;
}

/**
 * Vollständige Jahre (und Bruchteil) zwischen zwei Daten.
 */
function inventur_years_between(DateTimeImmutable $from, DateTimeImmutable $to): float
{
    if ($to < $from) {
        return 0.0;
    }
    $diff = $from->diff($to);
    $y = (float)$diff->y;
    $m = (float)$diff->m;
    $d = (float)$diff->d;

    return $y + $m / 12.0 + $d / 365.25;
}

/**
 * Degressive Abschreibung: Restwert pro Jahr mit konstantem Prozentsatz p vom Restwert.
 * Wert = Neuwert * (1 - p/100)^t , t = Jahre seit Kauf.
 */
function inventur_declining_balance_value(float $newValue, float $ratePctPerYear, float $yearsElapsed): float
{
    $roundHalf = static fn(float $v): float => round($v * 2.0) / 2.0;
    if ($newValue <= 0 || $yearsElapsed <= 0) {
        return $roundHalf($newValue);
    }
    $rate = max(0.0, min(100.0, $ratePctPerYear));
    $factor = pow(1.0 - $rate / 100.0, $yearsElapsed);

    return $roundHalf(max(0.0, $newValue * $factor));
}

/**
 * @return array{zeitwert: float, jahre: float, neuwert: float, stichtag: string, berechnet_bis: string}
 */
function inventur_compute_preview(
    float $quantity,
    float $purchasePriceUnit,
    float $newValueInput,
    float $depreciationPct,
    string $purchasePeriodRaw,
    ?DateTimeImmutable $stichtag = null,
    ?string $disposalPeriodRaw = null
): array {
    $stichtag = $stichtag ?? new DateTimeImmutable('today');
    $purchase = inventur_parse_purchase_period($purchasePeriodRaw);
    $disposal = inventur_parse_purchase_period((string)$disposalPeriodRaw);
    $effectiveTo = $stichtag;
    if ($disposal !== null && $disposal < $effectiveTo) {
        $effectiveTo = $disposal;
    }
    $neuwert = $newValueInput > 0
        ? $newValueInput
        : round($quantity * $purchasePriceUnit, 2);

    if ($purchase === null || $neuwert <= 0) {
        return [
            'zeitwert' => inventur_declining_balance_value($neuwert, 0.0, 0.0),
            'jahre' => 0.0,
            'neuwert' => round($neuwert, 2),
            'stichtag' => $stichtag->format('Y-m-d'),
            'berechnet_bis' => $effectiveTo->format('Y-m-d'),
        ];
    }

    $jahre = inventur_years_between($purchase, $effectiveTo);
    $zeitwert = inventur_declining_balance_value($neuwert, $depreciationPct, $jahre);

    return [
        'zeitwert' => $zeitwert,
        'jahre' => round($jahre, 4),
        'neuwert' => round($neuwert, 2),
        'stichtag' => $stichtag->format('Y-m-d'),
        'berechnet_bis' => $effectiveTo->format('Y-m-d'),
    ];
}
