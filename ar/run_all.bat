@echo off
setlocal

set "SCRIPT_DIR=%~dp0"
set "PS_SCRIPT=%SCRIPT_DIR%run_all.ps1"

if not exist "%PS_SCRIPT%" (
  echo Error: run_all.ps1 not found at "%PS_SCRIPT%"
  exit /b 2
)

if "%~1"=="" (
  echo Usage:
  echo   run_all.bat ^<workbook-path^> [data-dir] [--dry-run] [--strict]
  echo.
  echo Example:
  echo   run_all.bat "\\server\share\Abrechnung Rehkitzrettung.xlsm" data --strict
  exit /b 2
)

set "WORKBOOK=%~1"
set "DATADIR=%~2"
if "%DATADIR%"=="" set "DATADIR=data"

set "DRYRUN="
set "STRICT="

:parse_flags
if /i "%~3"=="--dry-run" set "DRYRUN=-DryRun"
if /i "%~3"=="--strict" set "STRICT=-Strict"
if /i "%~4"=="--dry-run" set "DRYRUN=-DryRun"
if /i "%~4"=="--strict" set "STRICT=-Strict"

echo [run_all.bat] Calling PowerShell wrapper...
powershell -ExecutionPolicy Bypass -File "%PS_SCRIPT%" -Workbook "%WORKBOOK%" -DataDir "%DATADIR%" %DRYRUN% %STRICT%
set "RC=%ERRORLEVEL%"

if not "%RC%"=="0" (
  echo [run_all.bat] Failed with exit code %RC%
  exit /b %RC%
)

echo [run_all.bat] Completed successfully.
exit /b 0
