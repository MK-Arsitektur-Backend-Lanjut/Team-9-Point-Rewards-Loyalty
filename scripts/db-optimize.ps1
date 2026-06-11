Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Get-EnvValue {
    param(
        [string]$Path,
        [string]$Key
    )
    if (-not (Test-Path $Path)) {
        return $null
    }
    $value = Get-Content $Path | Where-Object { $_ -match "^$Key=" } | Select-Object -First 1
    if ($null -eq $value) { return $null }
    $value -replace "^$Key=", "" -replace '"', ''
}

$envFile = if (Test-Path '.env') { '.env' } elseif (Test-Path '.env.docker') { '.env.docker' } else { throw 'Tidak menemukan .env atau .env.docker' }

$dbHost = Get-EnvValue -Path $envFile -Key 'DB_HOST'
$dbPort = Get-EnvValue -Path $envFile -Key 'DB_PORT'
$dbName = Get-EnvValue -Path $envFile -Key 'DB_DATABASE'
$dbUser = Get-EnvValue -Path $envFile -Key 'DB_USERNAME'
$dbPass = Get-EnvValue -Path $envFile -Key 'DB_PASSWORD'

if (-not $dbHost -or -not $dbName -or -not $dbUser) {
    throw 'DB_HOST, DB_DATABASE atau DB_USERNAME tidak ditemukan di file env.'
}

Write-Host "=== DB Optimization ==="
Write-Host "Using environment file: $envFile"
Write-Host "DB_HOST=$dbHost DB_PORT=$dbPort DB_DATABASE=$dbName"

if ($dbHost -eq 'mysql') {
    Write-Host "Using Docker MySQL service..."
    docker compose up -d mysql

    docker compose exec mysql sh -c "for t in \\$(mysql -uroot -prootsecret -D $dbName -N -e 'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=\\\"$dbName\\\"'); do echo Optimizing \\$t; mysql -uroot -prootsecret -D $dbName -e 'ANALYZE TABLE \\\$t; OPTIMIZE TABLE \\\$t;'; done"
    Write-Host "DB optimization complete."
    return
}

if ($dbHost -eq '127.0.0.1' -or $dbHost -eq 'localhost') {
    Write-Host "Using local MySQL client..."
    if (-not (Get-Command mysql -ErrorAction SilentlyContinue)) {
        throw 'Perintah mysql tidak ditemukan. Instal MySQL client atau jalankan optimasi lewat Docker.'
    }

    $passParam = if ($dbPass -ne '') { "-p$dbPass" } else { '' }
    $tables = mysql -h $dbHost -P $dbPort -u $dbUser $passParam -N -e "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=\"$dbName\";"

    foreach ($table in $tables) {
        Write-Host "Optimizing $table"
        mysql -h $dbHost -P $dbPort -u $dbUser $passParam -D $dbName -e "ANALYZE TABLE \\`$table\\`; OPTIMIZE TABLE \\`$table\\`;"
    }

    Write-Host "DB optimization complete."
    return
}

throw "DB host '$dbHost' tidak didukung oleh skrip ini. Gunakan host 'mysql', '127.0.0.1', atau 'localhost'."
