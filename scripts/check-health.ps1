# scripts/check-health.ps1
# Script de diagnóstico para el equipo de desarrollo
param(
    [string]$RenderUrl = "https://genesis-profesional.onrender.com/api/health",
    [string]$VercelUrl = "https://genesis-profesional-ugb.vercel.app/api/health"
)

Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "🏥 DIAGNOSTICO DE SALUD: GENESIS PROFESIONAL" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan

Write-Host "`n1. Verificando Backend en Render ($RenderUrl)..." -ForegroundColor Yellow
try {
    $resRender = Invoke-RestMethod -Uri $RenderUrl -Method Get -TimeoutSec 70
    Write-Host "✅ Render Backend ONLINE:" ($resRender | ConvertTo-Json -Compress) -ForegroundColor Green
} catch {
    Write-Host "❌ Render Backend OFFLINE o iniciando: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n2. Verificando Proxy de Vercel ($VercelUrl)..." -ForegroundColor Yellow
try {
    $resVercel = Invoke-RestMethod -Uri $VercelUrl -Method Get -TimeoutSec 70
    Write-Host "✅ Vercel Proxy ONLINE:" ($resVercel | ConvertTo-Json -Compress) -ForegroundColor Green
} catch {
    Write-Host "⚠️ Vercel Proxy no respondio (normal si aun no se ha desplegado): $($_.Exception.Message)" -ForegroundColor DarkGray
}

Write-Host "`nDiagnostico finalizado." -ForegroundColor Cyan
