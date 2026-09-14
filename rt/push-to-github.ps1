# Creates the first Git commit (if needed), sets GitHub remote, pushes main.
# Run from Explorer: right-click -> Run with PowerShell, OR in PowerShell:
#   Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass -Force
#   cd <folder containing this script>
#   .\push-to-github.ps1
$ErrorActionPreference = 'Stop'
$RepoUrl = 'https://github.com/ThomyChr/rt_ai.git'

Set-Location -LiteralPath $PSScriptRoot

if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    Write-Error 'git not found in PATH. Install Git for Windows or open Git Bash in this folder.'
}

if (-not (Test-Path -LiteralPath '.git')) {
    Write-Host 'Initializing repository and first commit...'
    git init
    git add .
    git status
    git commit -m 'Initial commit: rt_ai v1'
    if ($LASTEXITCODE -ne 0) {
        Write-Error 'git commit failed. Set user with: git config user.email "you@example.com"; git config user.name "Your Name"'
    }
}

git remote get-url origin 2>$null | Out-Null
if ($LASTEXITCODE -eq 0) {
    git remote set-url origin $RepoUrl
    Write-Host "Remote origin -> $RepoUrl"
} else {
    git remote add origin $RepoUrl
    Write-Host "Added remote origin -> $RepoUrl"
}

git branch -M main
Write-Host 'Pushing to GitHub (use a Personal Access Token if asked for password)...'
git push -u origin main
if ($LASTEXITCODE -ne 0) {
    Write-Host ''
    Write-Host 'If GitHub already has commits (e.g. README), run once:'
    Write-Host '  git pull origin main --allow-unrelated-histories'
    Write-Host '  git push -u origin main'
    exit $LASTEXITCODE
}
Write-Host 'First version uploaded. Done.'
