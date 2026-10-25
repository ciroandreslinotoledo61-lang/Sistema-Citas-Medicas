# Respaldo y recuperacion

Los scripts se entregan para PowerShell y PostgreSQL 18 nativos; no se ejecutaron esas herramientas en el entorno de esta entrega.

Deten escrituras. Guarda una copia privada del codigo, .git y ambos .env fuera del repositorio. Desde la raiz:

```powershell
$env:Path += ";C:\Program Files\PostgreSQL\18\bin"
New-Item -ItemType Directory -Force C:\RespaldosClinica
.\backend\scripts\respaldar-base.ps1 -BaseDatos citas_medicas -Usuario postgres -ArchivoDestino C:\RespaldosClinica\antes-v15.backup
```

La utilidad solicita la clave; no la pongas como argumento ni la subas a Git. El script rechaza sobrescribir un archivo, usa formato custom y verifica el indice con pg_restore --list. Ese listado no prueba restauracion completa. Si falla, un archivo parcial no es un respaldo valido.

Anota conteos de usuarios, pacientes, medicos, citas, consultas y tratamientos, fecha y version del codigo. Protege el respaldo porque contiene datos y hashes.

Crea en pgAdmin una base nueva vacia `clinica_restaurada_pruebas` y ensaya:

```powershell
.\backend\scripts\restaurar-en-pruebas.ps1 -BaseDestino clinica_restaurada_pruebas -Usuario postgres -ArchivoRespaldo C:\RespaldosClinica\antes-v15.backup
```

El script exige `_pruebas`, ausencia de tablas en public y restauracion en una transaccion con salida ante error. No borra ni limpia tablas. Omite propietarios/privilegios para facilitar la copia local; revisa permisos en el destino. Apunta una copia del codigo a esa base, compara conteos y abre registros conocidos antes de probar 006-008.

Si necesitas abandonar una actualizacion confirmada, respalda tambien el estado actual, restaura el respaldo previo en otra base vacia y usa la carpeta de codigo previa. Valida antes de sustituir el servicio. Las altas posteriores no existen en el respaldo anterior: conserva ambas bases para conciliarlas. No basta con revertir archivos Git ni se entrega un rollback que borre bitacora/versiones. No ejecutes tests sinteticos sobre la restauracion de informacion real.
