# Deploy maintenancecosts para servidor de producao
# Execute: powershell -ExecutionPolicy Bypass -File deploy-prod.ps1
#
# Preencha PROD_USER, PROD_PASS e PLUGIN_DST antes de usar.

$PROD_HOST = "atendimento.unifesp.br"
$PROD_USER = "SEU_USUARIO_SSH"
$PROD_PASS = "SUA_SENHA_SSH"
$PLUGIN_SRC = $PSScriptRoot
$PLUGIN_DST = "/var/www/html/glpi/plugins/maintenancecosts"   # ajuste se necessario

Write-Host "=== Deploy maintenancecosts -> PRODUCAO ===" -ForegroundColor Cyan
Write-Host "Host: $PROD_HOST" -ForegroundColor Yellow
Write-Host "Destino: $PLUGIN_DST" -ForegroundColor Yellow
Write-Host ""

if (-not (Get-Command "scp" -ErrorAction SilentlyContinue)) {
    Write-Host "ERRO: scp nao encontrado. Instale OpenSSH Client no Windows." -ForegroundColor Red
    exit 1
}

$plinkPath = "C:\Program Files\PuTTY\plink.exe"
$pscpPath  = "C:\Program Files\PuTTY\pscp.exe"

if ((Test-Path $plinkPath) -and (Test-Path $pscpPath)) {
    Write-Host "Usando PuTTY para deploy..." -ForegroundColor Yellow

    echo "y" | & $plinkPath -pw $PROD_PASS ${PROD_USER}@${PROD_HOST} "echo test" 2>&1 | Out-Null

    $files = @(
        "ajax/dropdown.php",
        "bootstrap.php",
        "front/about.php",
        "front/config.form.php",
        "front/costcenter.form.php",
        "front/import.form.php",
        "front/material.form.php",
        "front/price.form.php",
        "front/ticketmaterial.form.php",
        "hook.php",
        "install/install.sql",
        "install/uninstall.sql",
        "js/ticketmaterial.js",
        "js/ticketmaterial-v2.js",
        "setup.php",
        "src/Config.php",
        "src/CostCenter.php",
        "src/CostCenterLegacy.php",
        "src/Exporter.php",
        "src/FormcreatorCostCenterSync.php",
        "src/Importer.php",
        "src/Installer.php",
        "src/Material.php",
        "src/Menu.php",
        "src/Price.php",
        "src/Report.php",
        "src/TicketCostCenter.php",
        "src/TicketMaterial.php"
    )

    foreach ($f in $files) {
        $dir = Split-Path $f -Parent
        if ($dir) {
            & $plinkPath -pw $PROD_PASS ${PROD_USER}@${PROD_HOST} "mkdir -p ${PLUGIN_DST}/${dir}" 2>&1 | Out-Null
        }

        Write-Host "  -> $f" -ForegroundColor Gray
        & $pscpPath -pw $PROD_PASS "${PLUGIN_SRC}\$($f.Replace('/', '\'))" "${PROD_USER}@${PROD_HOST}:${PLUGIN_DST}/${f}" 2>&1 | Out-Null
    }

    Write-Host ""
    Write-Host "Ajustando ownership e limpando cache..." -ForegroundColor Yellow
    & $plinkPath -pw $PROD_PASS ${PROD_USER}@${PROD_HOST} "echo $PROD_PASS | sudo -S chown -R www-data:www-data ${PLUGIN_DST} && echo $PROD_PASS | sudo -S -u www-data php /var/www/html/glpi/bin/console glpi:cache:clear" 2>&1

    Write-Host "Deploy concluido!" -ForegroundColor Green

} else {
    Write-Host "PuTTY nao encontrado. Alternativas:" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "OPCAO 1 - OpenSSH (PowerShell):" -ForegroundColor Cyan
    Write-Host "  scp -r `"$PLUGIN_SRC\*`" ${PROD_USER}@${PROD_HOST}:${PLUGIN_DST}/"
    Write-Host ""
    Write-Host "OPCAO 2 - WinSCP (GUI):" -ForegroundColor Cyan
    Write-Host "  Conecte em ${PROD_USER}@${PROD_HOST} e copie $PLUGIN_SRC para $PLUGIN_DST"
    Write-Host ""
    Write-Host "Apos copiar, execute no servidor:" -ForegroundColor Cyan
    Write-Host "  sudo chown -R www-data:www-data $PLUGIN_DST"
    Write-Host "  sudo -u www-data php /var/www/html/glpi/bin/console glpi:cache:clear"
}
