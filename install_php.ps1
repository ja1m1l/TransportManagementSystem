[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
$ErrorActionPreference = "Stop"
$phpDir = "$env:USERPROFILE\php"

Write-Host "Creating $phpDir..."
New-Item -ItemType Directory -Force -Path $phpDir | Out-Null

Write-Host "Downloading PHP..."
$url = "https://windows.php.net/downloads/releases/php-8.3.17-nts-Win32-vs16-x64.zip"
$zipPath = "$phpDir\php.zip"
Invoke-WebRequest -Uri $url -OutFile $zipPath -UseBasicParsing

Write-Host "Extracting PHP..."
Expand-Archive -Path $zipPath -DestinationPath $phpDir -Force

Write-Host "Configuring PHP (Enabling SQLite)..."
Copy-Item "$phpDir\php.ini-development" "$phpDir\php.ini"
(Get-Content "$phpDir\php.ini") -replace ';extension_dir = "ext"', 'extension_dir = "ext"' | Set-Content "$phpDir\php.ini"
(Get-Content "$phpDir\php.ini") -replace ';extension=pdo_sqlite', 'extension=pdo_sqlite' | Set-Content "$phpDir\php.ini"
(Get-Content "$phpDir\php.ini") -replace ';extension=sqlite3', 'extension=sqlite3' | Set-Content "$phpDir\php.ini"

Write-Host "Adding PHP to User PATH..."
$userPath = [Environment]::GetEnvironmentVariable("Path", "User")
if ($userPath -notmatch [regex]::Escape($phpDir)) {
    $newPath = $userPath + ";$phpDir"
    [Environment]::SetEnvironmentVariable("Path", $newPath, "User")
}

Write-Host "Setting active session PATH..."
$env:PATH += ";$phpDir"

Write-Host "Cleaning up..."
Remove-Item $zipPath

Write-Host "PHP has been successfully installed and configured to $phpDir!"
