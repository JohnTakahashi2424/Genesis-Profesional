# ==============================================================================
# Script de sincronización segura de Render hacia PostgreSQL Local
# (NO BORRA NINGUNA TABLA NI DATO EXISTENTE - USA ON CONFLICT DO NOTHING)
# ==============================================================================

Write-Host "Iniciando descarga segura de datos desde Render Cloud..." -ForegroundColor Cyan

$renderUrl = "postgresql://genesis_profesional_db_user:ArvkpASR4jb4xEbKpqgkXPHdfQZ95FxD@dpg-dbj5lmmtlv9c73ca36qg-a.oregon-postgres.render.com/genesis_profesional_db"
$tempFile = [System.IO.Path]::GetTempFileName() + ".sql"
$pgDump = "C:\Program Files\PostgreSQL\18\bin\pg_dump.exe"
$psql = "C:\Program Files\PostgreSQL\18\bin\psql.exe"

try {
    Write-Host "1. Extrayendo datos de producción..." -ForegroundColor Yellow
    & $pgDump $renderUrl --data-only --inserts --on-conflict-do-nothing -f $tempFile
    
    if (Test-Path $tempFile) {
        Write-Host "2. Aplicando datos a PostgreSQL local (127.0.0.1:5432)..." -ForegroundColor Yellow
        $env:PGPASSWORD = "admin"
        & $psql -h 127.0.0.1 -U postgres -d genesis_profesional_db -f $tempFile | Out-Null
        Remove-Item Env:\PGPASSWORD
        
        Write-Host "Sincronizacion completada con exito sin borrar nada!" -ForegroundColor Green
    }
} catch {
    Write-Host "Error en la sincronizacion: $_" -ForegroundColor Red
} finally {
    if (Test-Path $tempFile) {
        Remove-Item -Path $tempFile -Force -ErrorAction SilentlyContinue
    }
}
