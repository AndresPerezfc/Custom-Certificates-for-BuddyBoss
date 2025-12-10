# Script para crear releases del plugin Custom Certificates (Windows PowerShell)
# Uso: .\build-release.ps1 -Version "1.0.0"

param(
    [Parameter(Mandatory=$false)]
    [string]$Version
)

# Si no se proporciona versión, pedirla
if (-not $Version) {
    $Version = Read-Host "Introduce la versión (ej: 1.0.0)"
}

Write-Host "`n=== Creando release v$Version ===`n" -ForegroundColor Green

# Variables
$PluginSlug = "custom-certificates"
$BuildDir = "build"
$FullZip = "$PluginSlug-full-v$Version.zip"
$LiteZip = "$PluginSlug-lite-v$Version.zip"

# Step 1: Limpiar builds anteriores
Write-Host "1. Limpiando builds anteriores..." -ForegroundColor Yellow
if (Test-Path $BuildDir) {
    Remove-Item -Recurse -Force $BuildDir
}
New-Item -ItemType Directory -Path $BuildDir | Out-Null

# Step 2: Copiar archivos del plugin
Write-Host "2. Copiando archivos del plugin..." -ForegroundColor Yellow

$SourceDir = Get-Location
$DestDir = Join-Path $BuildDir $PluginSlug

# Crear directorio destino
New-Item -ItemType Directory -Path $DestDir -Force | Out-Null

# Copiar archivos excluyendo ciertos directorios
$ExcludeDirs = @('build', '.git', '.idea', 'node_modules', 'vendor')
$ExcludeFiles = @('*.log', '.DS_Store', '*.sh', 'composer.lock', '*.ps1')

Get-ChildItem -Path . -Recurse | Where-Object {
    $item = $_
    $exclude = $false

    # Verificar directorios a excluir
    foreach ($dir in $ExcludeDirs) {
        if ($item.FullName -like "*\$dir\*" -or $item.Name -eq $dir) {
            $exclude = $true
            break
        }
    }

    # Verificar archivos a excluir
    foreach ($pattern in $ExcludeFiles) {
        if ($item.Name -like $pattern) {
            $exclude = $true
            break
        }
    }

    -not $exclude
} | Copy-Item -Destination {
    $destPath = Join-Path $DestDir $_.FullName.Substring($SourceDir.Path.Length)
    $destDir = Split-Path -Parent $destPath
    if (-not (Test-Path $destDir)) {
        New-Item -ItemType Directory -Path $destDir -Force | Out-Null
    }
    $destPath
} -Force

Write-Host "✓ Archivos copiados" -ForegroundColor Green

# Step 3: Instalar dependencias
Write-Host "3. Instalando dependencias para versión FULL..." -ForegroundColor Yellow

Push-Location (Join-Path $BuildDir $PluginSlug)

if (Get-Command composer -ErrorAction SilentlyContinue) {
    composer install --no-dev --optimize-autoloader --no-interaction
    Write-Host "✓ Dependencias instaladas" -ForegroundColor Green
} else {
    Write-Host "✗ Composer no está instalado. Instalando versión LITE solamente." -ForegroundColor Red
    $SkipFull = $true
}

Pop-Location

# Step 4: Crear ZIP FULL
if (-not $SkipFull) {
    Write-Host "4. Creando ZIP versión FULL..." -ForegroundColor Yellow

    $FullZipPath = Join-Path $PWD $FullZip
    if (Test-Path $FullZipPath) {
        Remove-Item $FullZipPath
    }

    Compress-Archive -Path (Join-Path $BuildDir $PluginSlug) -DestinationPath $FullZipPath -CompressionLevel Optimal

    $FullSize = (Get-Item $FullZipPath).Length / 1MB
    Write-Host "✓ $FullZip creado ($([math]::Round($FullSize, 2)) MB)" -ForegroundColor Green
}

# Step 5: Crear ZIP LITE (sin vendor)
Write-Host "5. Creando ZIP versión LITE..." -ForegroundColor Yellow

# Eliminar vendor temporalmente
$VendorPath = Join-Path $BuildDir "$PluginSlug\vendor"
if (Test-Path $VendorPath) {
    Rename-Item $VendorPath "$VendorPath.bak"
}

$LiteZipPath = Join-Path $PWD $LiteZip
if (Test-Path $LiteZipPath) {
    Remove-Item $LiteZipPath
}

Compress-Archive -Path (Join-Path $BuildDir $PluginSlug) -DestinationPath $LiteZipPath -CompressionLevel Optimal

# Restaurar vendor
if (Test-Path "$VendorPath.bak") {
    Rename-Item "$VendorPath.bak" $VendorPath
}

$LiteSize = (Get-Item $LiteZipPath).Length / 1MB
Write-Host "✓ $LiteZip creado ($([math]::Round($LiteSize, 2)) MB)" -ForegroundColor Green

# Step 6: Mostrar resumen
Write-Host "`n=== Builds completados ===`n" -ForegroundColor Green

if (-not $SkipFull) {
    Write-Host "Versión FULL: $([math]::Round($FullSize, 2)) MB - $FullZip" -ForegroundColor Cyan
}
Write-Host "Versión LITE: $([math]::Round($LiteSize, 2)) MB - $LiteZip" -ForegroundColor Cyan

# Step 7: Cleanup
Write-Host "`n¿Deseas limpiar el directorio de build? (S/N)" -ForegroundColor Yellow
$Cleanup = Read-Host

if ($Cleanup -eq 'S' -or $Cleanup -eq 's') {
    Remove-Item -Recurse -Force $BuildDir
    Write-Host "✓ Directorio de build limpiado" -ForegroundColor Green
}

Write-Host "`n=== ¡Release completado! ===`n" -ForegroundColor Green
Write-Host "Próximos pasos:"
Write-Host "1. Prueba los ZIPs en un WordPress limpio"
Write-Host "2. Sube a GitHub Releases"
Write-Host "3. Actualiza CHANGELOG.md"
Write-Host "4. Crea tag: git tag -a v$Version -m 'Release v$Version'"
Write-Host ""
