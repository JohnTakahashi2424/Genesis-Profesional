# scripts/wake-render.ps1
# Envia una senal de ping a Render para mitigar el Cold Start (50s) antes de realizar pruebas
param(
    [string]$Url = "https://genesis-profesional.onrender.com/api/health"
)

Write-Host "⏰ Enviando senal de activacion a Render ($Url)..." -ForegroundColor Yellow
Write-Host "Nota: Si el contenedor gratuito esta dormido, tomara entre 40 y 60 segundos en responder." -ForegroundColor DarkGray

$sw = [System.Diagnostics.Stopwatch]::StartNew()
try {
    $response = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 90
    $sw.Stop()
    Write-Host "🎉 Servidor despierto en $($sw.Elapsed.TotalSeconds.ToString('F1')) segundos (Status: $($response.StatusCode))" -ForegroundColor Green
} catch {
    Write-Host "⚠️ El servidor tardo mas de lo esperado o reporto error: $($_.Exception.Message)" -ForegroundColor Red
}
