# Registra (o reemplaza) las tareas programadas de mantenimiento de CINFSA en Windows.
# Ejecutar UNA VEZ en PowerShell abierto como Administrador:
#   powershell -ExecutionPolicy Bypass -File C:\xampp\htdocs\CINFSA1\scripts\instalar_tareas_windows.ps1
#
# Cada tarea corre cada 15 minutos con php.exe y agrega su salida a logs\cron.log,
# así se puede verificar que se esté ejecutando.

$ErrorActionPreference = 'Stop'

$raiz = Split-Path -Parent $PSScriptRoot
$php  = 'C:\xampp\php\php.exe'
$log  = Join-Path $raiz 'logs\cron.log'

if (-not (Test-Path $php)) {
    throw "No se encontró PHP en $php. Editá la variable `$php con la ruta correcta."
}
New-Item -ItemType Directory -Force (Join-Path $raiz 'logs') | Out-Null

$tareas = @(
    @{ Nombre = 'Expirar Entradas CINFSA';         Script = 'expirar_entradas_cron.php' },
    @{ Nombre = 'Limpiar Reservas Expiradas CINFSA'; Script = 'limpiar_reservas_expiradas.php' }
)

foreach ($t in $tareas) {
    $script = Join-Path $raiz "scripts\$($t.Script)"
    # cmd /c para poder redirigir la salida al log
    $argumentos = "/c `"`"$php`" `"$script`" >> `"$log`" 2>&1`""
    $accion = New-ScheduledTaskAction -Execute 'cmd.exe' -Argument $argumentos -WorkingDirectory $raiz
    $disparador = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 15)
    $config = New-ScheduledTaskSettingsSet -StartWhenAvailable -DontStopIfGoingOnBatteries -AllowStartIfOnBatteries
    # Corre como SYSTEM: sin abrir una ventana de consola cada 15 minutos y aunque no haya sesión iniciada
    $usuario = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest

    Register-ScheduledTask -TaskName $t.Nombre -Action $accion -Trigger $disparador -Settings $config -Principal $usuario -Force | Out-Null
    Write-Host "OK: '$($t.Nombre)' -> $($t.Script) cada 15 minutos"
}

Write-Host ""
Write-Host "Listo. En unos minutos revisá $log para confirmar que corren."
