param(
    [Parameter(Mandatory = $true)]
    [string]$Workbook,

    [string]$DataDir = "data",

    [switch]$DryRun,

    [switch]$Strict
)

$ErrorActionPreference = "Stop"

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$importer = Join-Path $scriptDir "excel_importer.py"
$validator = Join-Path $scriptDir "validate_json.py"

if (-not (Test-Path -LiteralPath $importer)) {
    throw "Importer not found: $importer"
}
if (-not (Test-Path -LiteralPath $validator)) {
    throw "Validator not found: $validator"
}
if (-not (Test-Path -LiteralPath $Workbook)) {
    throw "Workbook not found: $Workbook"
}

$resolvedDataDir = if ([System.IO.Path]::IsPathRooted($DataDir)) {
    $DataDir
} else {
    Join-Path $scriptDir $DataDir
}

if (-not (Test-Path -LiteralPath $resolvedDataDir)) {
    New-Item -ItemType Directory -Path $resolvedDataDir -Force | Out-Null
}

$importArgs = @(
    $importer,
    "--workbook", $Workbook,
    "--output-dir", $resolvedDataDir
)
if ($DryRun) {
    $importArgs += "--dry-run"
}

Write-Host "[1/2] Running importer..."
& python @importArgs
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}

$validateArgs = @(
    $validator,
    "--data-dir", $resolvedDataDir
)
if ($Strict) {
    $validateArgs += "--strict"
}

Write-Host "[2/2] Running validator..."
& python @validateArgs
exit $LASTEXITCODE
