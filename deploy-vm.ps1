# Deploy maintenancecosts para VM de homologacao
# Execute: powershell -ExecutionPolicy Bypass -File deploy-vm.ps1

$VM_HOST = "192.168.159.129"
$VM_USER = "codex"
$VM_PASS = "codex"
$PLUGIN_SRC = $PSScriptRoot
$PLUGIN_DST = "/var/www/html/glpi/plugins/maintenancecosts"

Write-Host "=== Deploy maintenancecosts -> VM ===" -ForegroundColor Cyan

if (-not (Get-Command "scp" -ErrorAction SilentlyContinue)) {
    Write-Host "ERRO: scp nao encontrado. Instale OpenSSH Client no Windows." -ForegroundColor Red
    exit 1
}

$plinkPath = "C:\Program Files\PuTTY\plink.exe"
$pscpPath  = "C:\Program Files\PuTTY\pscp.exe"

if ((Test-Path $plinkPath) -and (Test-Path $pscpPath)) {
    Write-Host "Usando PuTTY para deploy..." -ForegroundColor Yellow

    echo "y" | & $plinkPath -pw $VM_PASS ${VM_USER}@${VM_HOST} "echo test" 2>&1 | Out-Null

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
        "js/ticketmaterial-v3.js",
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
            & $plinkPath -pw $VM_PASS ${VM_USER}@${VM_HOST} "mkdir -p ${PLUGIN_DST}/${dir}" 2>&1 | Out-Null
        }

        Write-Host "  -> $f" -ForegroundColor Gray
        & $pscpPath -pw $VM_PASS "${PLUGIN_SRC}\$($f.Replace('/', '\'))" "${VM_USER}@${VM_HOST}:${PLUGIN_DST}/${f}" 2>&1 | Out-Null
    }

    Write-Host "Ajustando ownership e limpando cache..." -ForegroundColor Yellow
    & $plinkPath -pw $VM_PASS ${VM_USER}@${VM_HOST} "echo $VM_PASS | sudo -S chown -R www-data:www-data ${PLUGIN_DST} && echo $VM_PASS | sudo -S -u www-data php /var/www/html/glpi/bin/console glpi:cache:clear" 2>&1

    Write-Host "Deploy concluido!" -ForegroundColor Green
} else {
    Write-Host ""
    Write-Host "PuTTY nao encontrado. Use um dos metodos abaixo:" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "OPCAO 1 - WinSCP (GUI):" -ForegroundColor Cyan
    Write-Host "  1. Abra WinSCP, conecte em ${VM_USER}@${VM_HOST} com senha '${VM_PASS}'"
    Write-Host "  2. Copie a pasta $PLUGIN_SRC para ${PLUGIN_DST}"
    Write-Host ""
    Write-Host "OPCAO 2 - PowerShell com OpenSSH:" -ForegroundColor Cyan
    Write-Host "  scp -r `"$PLUGIN_SRC\*`" ${VM_USER}@${VM_HOST}:${PLUGIN_DST}/"
    Write-Host ""
    Write-Host "OPCAO 3 - Linux/WSL:" -ForegroundColor Cyan
    Write-Host "  rsync -avz -e ssh /caminho/plugin/ codex@192.168.159.129:/var/www/html/glpi/plugins/maintenancecosts/"
    Write-Host ""
    Write-Host "Apos copiar, execute na VM:" -ForegroundColor Cyan
    Write-Host "  sudo chown -R www-data:www-data /var/www/html/glpi/plugins/maintenancecosts"
}
