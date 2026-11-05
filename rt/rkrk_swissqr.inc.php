<?php
declare(strict_types=1);

/**
 * Swiss-QR für die bestehenden Einzahlungsscheine.
 * Liefert nur das QR-Bild. Layout und CSS bleiben bei den Aufrufern.
 */

if (!function_exists('iconv')) {
    function iconv(string $from, string $to, string $string): string|false
    {
        $toBase = preg_replace('/\/\/.*$/', '', $to) ?? $to;
        if (0 === strcasecmp($from, $toBase)) {
            return $string;
        }
        try {
            return mb_convert_encoding($string, $toBase, $from);
        } catch (Throwable) {
            return false;
        }
    }
}

function rkrk_swissqr_boot(): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    $autoload = dirname(__DIR__) . '/lib/vendor/autoload.php';
    if (!is_file($autoload)) {
        $ready = false;
        return false;
    }
    require_once $autoload;
    $ready = true;
    return true;
}

function rkrk_swissqr_cut(string $value, int $max): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $max);
    }
    return substr($value, 0, $max);
}

function rkrk_swissqr_country(string $value): string
{
    $value = strtoupper(trim($value));
    return preg_match('/^[A-Z]{2}$/', $value) ? $value : 'CH';
}

/**
 * @param array{name?:string,street?:string,nr?:string,zip?:string,city?:string,country?:string,iban?:string} $creditor
 * @param array{name?:string,street?:string,nr?:string,zip?:string,city?:string,country?:string} $debtor
 */
function rkrk_swissqr_bill(array $creditor, array $debtor, ?float $amount, string $currency = 'CHF', string $info = ''): ?Sprain\SwissQrBill\QrBill
{
    if (!rkrk_swissqr_boot()) {
        return null;
    }

    $iban = strtoupper(preg_replace('/\s+/', '', (string)($creditor['iban'] ?? '')) ?? '');
    $name = rkrk_swissqr_cut((string)($creditor['name'] ?? ''), 70);
    if ($iban === '' || $name === '') {
        return null;
    }

    $currency = strtoupper(trim($currency));
    if (!in_array($currency, ['CHF', 'EUR'], true)) {
        $currency = 'CHF';
    }

    $street = rkrk_swissqr_cut((string)($creditor['street'] ?? ''), 70);
    $nr = rkrk_swissqr_cut((string)($creditor['nr'] ?? ''), 16);
    $zip = rkrk_swissqr_cut((string)($creditor['zip'] ?? ''), 16);
    $city = rkrk_swissqr_cut((string)($creditor['city'] ?? ''), 35);
    $country = rkrk_swissqr_country((string)($creditor['country'] ?? 'CH'));
    if ($zip === '' || $city === '') {
        return null;
    }

    $qrBill = Sprain\SwissQrBill\QrBill::create();
    if ($street === '') {
        $qrBill->setCreditor(Sprain\SwissQrBill\DataGroup\Element\StructuredAddress::createWithoutStreet(
            $name,
            $zip,
            $city,
            $country
        ));
    } else {
        $qrBill->setCreditor(Sprain\SwissQrBill\DataGroup\Element\StructuredAddress::createWithStreet(
            $name,
            $street,
            $nr === '' ? null : $nr,
            $zip,
            $city,
            $country
        ));
    }
    $qrBill->setCreditorInformation(Sprain\SwissQrBill\DataGroup\Element\CreditorInformation::create($iban));

    $debtorName = rkrk_swissqr_cut((string)($debtor['name'] ?? ''), 70);
    $debtorZip = rkrk_swissqr_cut((string)($debtor['zip'] ?? ''), 16);
    $debtorCity = rkrk_swissqr_cut((string)($debtor['city'] ?? ''), 35);
    if ($debtorName !== '' && $debtorZip !== '' && $debtorCity !== '') {
        $debtorStreet = rkrk_swissqr_cut((string)($debtor['street'] ?? ''), 70);
        $debtorNr = rkrk_swissqr_cut((string)($debtor['nr'] ?? ''), 16);
        $debtorCountry = rkrk_swissqr_country((string)($debtor['country'] ?? 'CH'));
        if ($debtorStreet === '') {
            $qrBill->setUltimateDebtor(Sprain\SwissQrBill\DataGroup\Element\StructuredAddress::createWithoutStreet(
                $debtorName,
                $debtorZip,
                $debtorCity,
                $debtorCountry
            ));
        } else {
            $qrBill->setUltimateDebtor(Sprain\SwissQrBill\DataGroup\Element\StructuredAddress::createWithStreet(
                $debtorName,
                $debtorStreet,
                $debtorNr === '' ? null : $debtorNr,
                $debtorZip,
                $debtorCity,
                $debtorCountry
            ));
        }
    }

    $amountValue = null;
    if ($amount !== null && $amount > 0) {
        $amountValue = round($amount, 2);
    }
    $qrBill->setPaymentAmountInformation(
        Sprain\SwissQrBill\DataGroup\Element\PaymentAmountInformation::create($currency, $amountValue)
    );
    $qrBill->setPaymentReference(Sprain\SwissQrBill\DataGroup\Element\PaymentReference::create(
        Sprain\SwissQrBill\DataGroup\Element\PaymentReference::TYPE_NON
    ));

    $info = rkrk_swissqr_cut($info, 140);
    if ($info !== '') {
        $qrBill->setAdditionalInformation(Sprain\SwissQrBill\DataGroup\Element\AdditionalInformation::create($info));
    }

    if (count($qrBill->getViolations()) !== 0) {
        return null;
    }

    return $qrBill;
}

/**
 * @param array{name?:string,street?:string,nr?:string,zip?:string,city?:string,country?:string,iban?:string} $creditor
 * @param array{name?:string,street?:string,nr?:string,zip?:string,city?:string,country?:string} $debtor
 */
function rkrk_swissqr_svg(array $creditor, array $debtor, ?float $amount, string $currency = 'CHF', string $info = ''): string
{
    $qrBill = rkrk_swissqr_bill($creditor, $debtor, $amount, $currency, $info);
    if ($qrBill === null) {
        return '';
    }
    try {
        return $qrBill->getQrCode()->getAsString('svg');
    } catch (Throwable) {
        return '';
    }
}

function rkrk_swissqr_img(string $svg, string $css = 'width:40mm;height:40mm;display:block;'): string
{
    if ($svg === '' || strpos($svg, '<svg') === false) {
        return '';
    }
    $cssEsc = htmlspecialchars($css, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return '<img src="data:image/svg+xml;base64,' . base64_encode($svg) . '" style="' . $cssEsc . '" alt="Swiss QR-Code">';
}

/**
 * @param array{name?:string,street?:string,nr?:string,zip?:string,city?:string,country?:string,iban?:string} $creditor
 * @param array{name?:string,street?:string,nr?:string,zip?:string,city?:string,country?:string} $debtor
 */
function rkrk_swissqr_png(array $creditor, array $debtor, ?float $amount, string $currency = 'CHF', string $info = ''): string
{
    $qrBill = rkrk_swissqr_bill($creditor, $debtor, $amount, $currency, $info);
    if ($qrBill === null) {
        return '';
    }
    try {
        return $qrBill->getQrCode()->getAsString('png');
    } catch (Throwable) {
        return '';
    }
}
