# TIS deploy paketini hazırlayır.
# Çıktı: deploy/public_html/ — sunucuya bu klasörün tamamını yükleyin.

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
$out  = Join-Path $PSScriptRoot "public_html"
$tis  = Join-Path $out "TIS"

Write-Host "Deploy paketi hazırlanıyor..." -ForegroundColor Cyan

# TIS altını temizle (index.php və .htaccess saxlanır)
if (Test-Path $tis) {
    Remove-Item $tis -Recurse -Force
}

New-Item -ItemType Directory -Path $tis -Force | Out-Null

# Tətbiq faylları
$items = @("src", "vendor", "uploads", "composer.json", "composer.lock")
foreach ($item in $items) {
    $src = Join-Path $root $item
    if (Test-Path $src) {
        Copy-Item $src (Join-Path $tis $item) -Recurse -Force
        Write-Host "  + TIS/$item" -ForegroundColor Green
    } else {
        Write-Host "  ! tapılmadı: $item" -ForegroundColor Yellow
    }
}

$login = Join-Path $tis "src\All\Login.php"
if (-not (Test-Path $login)) {
    Write-Error "Login.php tapılmadı: $login"
}

Write-Host ""
Write-Host "Hazırdır. Sunucuya yükləyin:" -ForegroundColor Cyan
Write-Host "  deploy/public_html/  ->  public_html/" -ForegroundColor White
Write-Host ""
Write-Host "Gözlənilən yol:" -ForegroundColor Cyan
Write-Host "  public_html/TIS/src/All/Login.php" -ForegroundColor White
