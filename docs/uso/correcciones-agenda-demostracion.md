# Correcciones de agenda y demostración local

## Reglas y alcance

- Los bloques se filtran por `idMedico`, fecha y página; Administración dispone de "Todos los médicos", nombres, especialidad e ID para distinguir homónimos. Con más de 15 perfiles se habilita una búsqueda **local de opciones**, no un filtro SQL por texto libre. Se incluyen perfiles inactivos para consultar bloques anteriores.
- El médico ve automáticamente sus bloques, conserva la fecha y no recibe un selector de otros profesionales ni la columna redundante. El alcance se aplica en backend por `usuario.idUsuario` de sesión.
- Se mantienen los permisos: solo Administración programa bloques. Recepción conserva disponibilidad y agenda, no recibe administración de bloques. Pacientes no acceden a `/horarios`.
- La búsqueda de consultas ya funcionaba a través de `components/lista-paginada.js`: transmite `busqueda`, fechas y página. Se añadió una regresión sin cambiar esa funcionalidad.
- Nuevos bloques/turnos, reservas y destinos de reprogramación son de **30 minutos**, independientemente de variables externas o datos HTTP. `CITA_DURACION_MINUTOS` deja de configurar la duración; puede permanecer en configuraciones antiguas, sin efecto.
- Turnos legados de otra duración dejan de ofrecerse para nuevas reservas y se rechazan si se envía su ID directamente. **No se cambian citas, turnos ni bloques existentes**; la consulta histórica se conserva. Una reprogramación fallida conserva su cita original.
- Se mantienen apertura, cierre, límites de bloque, cruces, transacciones y límite de una cita activa. Los descansos son intervalos sin bloques (por ejemplo 08:00-12:00 y 14:00-17:00); no existe un calendario de descansos independiente y no se inventó una pausa global.
- La disponibilidad exige perfil médico, rol `medico`, cuenta activa y especialidad activa; la escritura revalida estos requisitos bajo bloqueo. La agenda histórica no excluye un perfil por estar inactivo o tener una inconsistencia de rol.
- Registro normal de médicos/pacientes crea una cuenta y su perfil con el rol correspondiente. No se modificaron esos mecanismos ni se impuso exclusión entre perfiles.
- Los dos Saturnino Mamani (`id_usuario` 8 y 9, según la comprobación del propietario) no se tocaron. Los nombres no se usan como claves de relación. Sin conexión a Neon no se certifican sus registros ni los de otras cuentas de producción.

## Seed de seis cuentas ficticias

Archivo CLI: `backend/scripts/cargar-demostracion.php`. Implementación: `backend/src/data/seeders/DemostracionSeeder.php`. No se ejecuta desde la aplicación ni desde el despliegue.

| Cuenta | Correo | Perfil |
| --- | --- | --- |
| Médico 1 | medico1@demo.vidasalud.test | Medicina general |
| Médico 2 | medico2@demo.vidasalud.test | Pediatría |
| Médico 3 | medico3@demo.vidasalud.test | Dermatología |
| Paciente 1 | paciente1@demo.vidasalud.test | Paciente con historia clínica |
| Paciente 2 | paciente2@demo.vidasalud.test | Paciente con historia clínica |
| Paciente 3 | paciente3@demo.vidasalud.test | Paciente con historia clínica |

Usa una base **independiente y local**, vacía o con datos exclusivamente sintéticos, con las migraciones 001-010 instaladas. El seed puede agregar los roles médico/paciente y especialidades que falten; nunca actualiza cuentas, claves ni especialidades existentes. No crea horarios o citas automáticamente. Para probar gestión administrativa se necesitan las cuentas administrativas de la instalación local normal.

Preparación en una terminal PowerShell dedicada (no modificar `.env` ni usar un túnel hacia producción):

```powershell
$env:APP_ENV = 'local'
$env:DB_HOST = '127.0.0.1'
$env:DB_PORT = '5432'  # Puerto del PostgreSQL local de pruebas
$env:DB_NAME = 'vidasalud_demo_pruebas'
$env:DB_USER = 'usuario_local_de_pruebas'
$env:DB_SSLMODE = 'disable'
$secretoBD = Read-Host 'Clave PostgreSQL LOCAL' -AsSecureString
$env:DB_PASS = [System.Net.NetworkCredential]::new('', $secretoBD).Password
$secretoDemo = Read-Host 'Clave de demostración: 12-72 bytes, letras y números, sin patrones repetidos' -AsSecureString
$env:DEMO_SEED_PASSWORD = [System.Net.NetworkCredential]::new('', $secretoDemo).Password
$env:DEMO_SEED_ENABLE = '1'
try {
    & C:/php85/php.exe backend/scripts/cargar-demostracion.php
} finally {
    Remove-Item Env:DEMO_SEED_ENABLE, Env:DEMO_SEED_PASSWORD, Env:DB_PASS -ErrorAction SilentlyContinue
    $secretoBD.Dispose()
    $secretoDemo.Dispose()
}
```

Cierra después esa terminal. Las variables del proceso prevalecen sobre `.env`. No escribir claves literales en comandos, scripts o documentación. Este seed comparte una clave de prueba elegida externamente entre las seis cuentas; no usarla en ningún entorno real.

Protecciones: rechazo **antes de conectar** si falta habilitación, si el entorno no es `local/test`, si el host no es loopback, el puerto es inválido o la base no termina en `_pruebas`. También comprueba `current_database()` y la dirección real del servidor antes de escribir. No admite Neon ni otros hosts remotos, aunque lleven nombre de pruebas. No redirigir puertos locales a producción.

Repetir conserva IDs y contraseñas. Si un correo reservado ya pertenece a una cuenta incompatible, se detiene sin sobrescribirla. No reactiva especialidades existentes; rechaza especialidades ambiguas. Reutiliza las especialidades legadas sin tilde cuando hay una única coincidencia. Usa los servicios normales y una transacción por cuenta; si una alta falla, las anteriores se conservan y puede repetirse tras resolver el conflicto. Un bloqueo asesor serializa ejecuciones del propio seed. No borra ni corrige cuentas.

## Verificación y pendientes

Los comandos PHP y PostgreSQL aislado están en [pruebas locales](pruebas-php.md). Para los filtros JavaScript, sin red ni dependencias:

```powershell
node --experimental-vm-modules backend/tests/test_filtros_listados.mjs
```

Esta prueba ejecuta los módulos reales de listados con DOM y API controlados: búsqueda de consultas por nombre/CI, fechas, paginación, selector por IDs, homónimos, filtro local, error de carga y columnas por rol. No sustituye la revisión visual en navegador.

Antes de publicar, comprobar manualmente en la base local:

1. Selector con pocos y muchos médicos, homónimos e inactivos; fecha, anterior/siguiente y estados vacíos/error.
2. Administración programa; médico consulta solo propios y no puede crear; Recepción y paciente mantienen sus accesos actuales.
3. Ventanas separadas para dos pacientes compitiendo por un turno; confirmar mensajes de conflicto.
4. Dos bloques separados no ofrecen turnos en el descanso. Nuevas reservas/reprogramaciones duran 30 minutos y los registros legados no cambian.
5. Diseño responsive del selector y controles con teclado/lector de pantalla.

No hay migraciones nuevas. No ejecutar otra vez 009/010 en Neon. El envío real de Resend, las cookies/HTTPS en Render, la revisión de relaciones efectivas en producción y cualquier política futura de doble perfil siguen pendientes de comprobación/autorización específica. No se ampliaron permisos clínicos.
