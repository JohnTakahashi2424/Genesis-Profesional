# ==============================================================================
# Script de sincronización de Render hacia PostgreSQL Local
# (NO BORRA NINGUNA TABLA NI DATO EXISTENTE - USA ON CONFLICT DO NOTHING)
# ==============================================================================

Write-Host "Iniciando descarga segura de datos desde Render Cloud..." -ForegroundColor Cyan

# 1. Obtener URL de Render de forma segura (sin credenciales hardcodeadas en código)
$renderUrl = $env:RENDER_DATABASE_URL

if (-not $renderUrl -and (Test-Path ".env")) {
    $envLines = Get-Content ".env" | Where-Object { $_ -match "^RENDER_DATABASE_URL=" }
    if ($envLines) {
        $renderUrl = ($envLines -split "=", 2)[1].Trim().Trim('"').Trim("'")
    }
}

if (-not $renderUrl) {
    Write-Host "AVISO: Variable RENDER_DATABASE_URL no encontrada en el entorno ni en .env." -ForegroundColor Yellow
    $secureInput = Read-Host "Por favor, ingresa la URL de conexión de Render PostgreSQL" -AsSecureString
    if ($secureInput) {
        $BSTR = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($secureInput)
        $renderUrl = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($BSTR)
    }
}

if (-not $renderUrl) {
    Write-Error "Operación cancelada: No se proporcionó la URL de conexión a la base de datos."
    exit 1
}

$tempFile = [System.IO.Path]::GetTempFileName() + ".sql"
$pgDump = "C:\Program Files\PostgreSQL\18\bin\pg_dump.exe"
$psql = "C:\Program Files\PostgreSQL\18\bin\psql.exe"

try {
    Write-Host "1. Extrayendo datos de producción..." -ForegroundColor Yellow
    & $pgDump $renderUrl --data-only --inserts --on-conflict-do-nothing -f $tempFile
    
    if (Test-Path $tempFile) {
        Write-Host "2. Aplicando datos a PostgreSQL local (127.0.0.1:5432)..." -ForegroundColor Yellow
        $localPass = if ($env:LOCAL_DB_PASSWORD) { $env:LOCAL_DB_PASSWORD } else { "admin" }
        $env:PGPASSWORD = $localPass
        & $psql -h 127.0.0.1 -U postgres -d genesis_profesional_db -f $tempFile | Out-Null
        Remove-Item Env:\PGPASSWORD -ErrorAction SilentlyContinue
        
        Write-Host "Sincronizacion completada con exito sin borrar nada!" -ForegroundColor Green
    }
} catch {
    Write-Host "Error en la sincronizacion: $_" -ForegroundColor Red
} finally {
    if (Test-Path $tempFile) {
        Remove-Item -Path $tempFile -Force -ErrorAction SilentlyContinue
    }
}
