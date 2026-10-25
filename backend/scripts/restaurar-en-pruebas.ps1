param([Parameter(Mandatory=$true)][string]$BaseDestino,[Parameter(Mandatory=$true)][string]$Usuario,[Parameter(Mandatory=$true)][string]$ArchivoRespaldo,[string]$Servidor='127.0.0.1',[int]$Puerto=5432)
$ErrorActionPreference='Stop'
if(!$BaseDestino.EndsWith('_pruebas')){throw 'El destino debe terminar en _pruebas.'}
if(!(Test-Path -LiteralPath $ArchivoRespaldo)){throw 'No existe el respaldo.'}
$Tablas=& psql --host=$Servidor --port=$Puerto --username=$Usuario --password --dbname=$BaseDestino -X -A -t -v ON_ERROR_STOP=1 -c "SELECT count(*) FROM information_schema.tables WHERE table_schema='public' AND table_type='BASE TABLE'"
if($LASTEXITCODE -ne 0 -or $Tablas.Trim() -ne '0'){throw 'La base destino debe existir y estar vacia. No se elimino nada.'}
& pg_restore --host=$Servidor --port=$Puerto --username=$Usuario --password --dbname=$BaseDestino --no-owner --no-privileges --single-transaction --exit-on-error $ArchivoRespaldo
if($LASTEXITCODE -ne 0){throw 'Restauracion fallida y revertida.'}
Write-Host 'Restauracion aislada completada.'
