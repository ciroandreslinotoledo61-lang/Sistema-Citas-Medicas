param([Parameter(Mandatory=$true)][string]$BaseDatos,[Parameter(Mandatory=$true)][string]$Usuario,[Parameter(Mandatory=$true)][string]$ArchivoDestino,[string]$Servidor='127.0.0.1',[int]$Puerto=5432)
$ErrorActionPreference='Stop'
if(Test-Path -LiteralPath $ArchivoDestino){throw 'El archivo existe. Usa un nombre nuevo.'}
$Carpeta=Split-Path -Parent ([IO.Path]::GetFullPath($ArchivoDestino))
if(!(Test-Path -LiteralPath $Carpeta)){throw 'Crea la carpeta fuera del repositorio.'}
& pg_dump --host=$Servidor --port=$Puerto --username=$Usuario --password --dbname=$BaseDatos --format=custom --file=$ArchivoDestino
if($LASTEXITCODE -ne 0){throw 'pg_dump fallo. El archivo parcial no es un respaldo valido.'}
& pg_restore --list $ArchivoDestino | Out-Null
if($LASTEXITCODE -ne 0){throw 'No se pudo verificar el indice del respaldo.'}
Write-Host 'Respaldo creado. Verifica la restauracion aislada antes de actualizar.'
