# Clínica Vida Salud — guía para agentes

## Contexto y límites

Sistema de citas médicas para Bolivia. Stack: PHP 8.5, PostgreSQL 18/PDO y frontend PHP, HTML, CSS y JavaScript ES modules sin framework. Producción usa Render y Neon; el repositorio local no autoriza operar sobre ellos.

- Conservar la rama actual y el trabajo ajeno. No crear ramas, hacer commits, push ni despliegues sin autorización expresa.
- No modificar `.env`, secretos, cuentas existentes ni producción. No ejecutar migraciones ni seeds en Neon sin autorización específica.
- Preferir correcciones pequeñas y pruebas focalizadas; no refactorizar toda la arquitectura ni agregar dependencias innecesarias.
- No utilizar datos reales de pacientes en pruebas ni imprimir contraseñas, hashes o tokens.

## Arquitectura

- `backend/src/presentation/`: rutas HTTP, controladores y middleware. Los controladores traducen solicitudes/respuestas y llaman servicios; no contienen SQL.
- `backend/src/business/services/`: permisos, reglas funcionales y coordinación transaccional. Sin SQL directo ni superglobales HTTP.
- `backend/src/business/validators/`: identificadores, fechas, filtros, datos y credenciales de entrada.
- `backend/src/data/models/`: entidades y proyecciones públicas; no exponer secretos.
- `backend/src/data/repositories/`: SQL parametrizado y filtros de alcance por identificadores. No decidir permisos de sesión aquí.
- `backend/src/data/config/`, `migrations/`, `seeders/`: conexión, transacciones, SQL incremental y datos sintéticos.
- `backend/src/shared/`: configuración, constantes de roles/permisos, sesión, errores y utilidades.
- `frontend/src/features/<modulo>/pages/`: vistas PHP y lógica JS de cada página.
- `services/`: llamadas a API por módulo; reutilizar `frontend/src/api/cliente-api.js`.
- `components/`: fragmentos/componentes reutilizables del módulo; los comunes están en `frontend/src/components/`.
- `utils/`: funciones de presentación o transformación sin acceso directo a base de datos. Crear estas carpetas solo cuando hagan falta.
- `frontend/src/app/`, `routes/`, `layouts/`, `styles/`: mapa de páginas, protección, plantilla y diseño compartido.
- `database/sql-estudio/`: ejemplos didácticos, no migraciones de producción.
- `deploy/`: configuración de publicación; `docs/uso/` y `README.md`: instrucciones operativas.

## Reglas funcionales y permisos

La fuente de verdad es `PermisosSistema.php` y las restricciones de los servicios, no los botones del frontend.

| Rol | Alcance conservado |
| --- | --- |
| Administrador | Usuarios, médicos, especialidades, pacientes, programación y consulta de bloques, citas, reportes y bitácora. Sin acceso clínico. |
| Recepcionista | Pacientes, disponibilidad, agenda y gestión administrativa de citas. No administra ni consulta bloques por `/horarios`; no accede al historial clínico. |
| Médico | Horarios y citas propios; consultas propias e historial de pacientes con relación de atención autorizada. No programa bloques ni reprograma citas. |
| Paciente | Perfil, citas e historial propios; disponibilidad para reservar. No administra bloques. |

- Toda cita nueva dura exactamente **30 minutos**. `ConfiguracionAgenda` ignora `CITA_DURACION_MINUTOS`; no convertir registros históricos. Reservas y reprogramaciones rechazan turnos de otra duración.
- Respetar apertura/cierre y límites del bloque. Los descansos se representan dejando intervalos sin bloques; no inventar un descanso general.
- Solo una cita `programada` activa por paciente, incluidas las pendientes de resultado tras su hora; conservar excepciones legadas previstas por SQL.
- Reservas con bloqueos/transacción y defensa SQL. Reprogramar debe revertirse por completo si falla; nunca borrar el origen.
- Cancelación/reprogramación antes del inicio según permisos; asistencia desde el inicio e inasistencia desde el fin.
- Usuarios, pacientes, médicos y horarios se relacionan por IDs, nunca por nombres. Homónimos no equivalen a duplicados. No imponer exclusión entre perfiles médico/paciente; una cuenta tiene un rol asignado.
- La disponibilidad nueva requiere perfil médico, rol `medico`, cuenta activa y especialidad activa. Los selectores de consulta histórica pueden incluir perfiles inactivos.

## Seguridad

- Verificar identidad desde sesión en backend y limitar consultas SQL por usuario/paciente/médico autorizado, incluso si el cliente envía otro ID.
- Conservar CSRF, cookies seguras, validaciones de contraseñas, expiración y uso único de claves temporales, `version_credenciales` e invalidación de sesiones.
- No ampliar acceso clínico de Administración/Recepción ni sustituir autorización por ocultar columnas.
- Recuperación con Resend permanece pendiente de configuración externa. Pruebas con correo simulado; no inventar API keys ni activar envíos reales.

## Migraciones y nuevas funcionalidades

1. Reutilizar servicio, repositorio, validador y componentes existentes del módulo.
2. Aplicar permisos y alcance de sesión antes de devolver o modificar datos; mantener paginación y filtros.
3. Usar SQL parametrizado, transacciones y control de concurrencia. No modificar migraciones históricas aplicadas: preparar una incremental separada si es necesaria.
4. Migraciones actuales: 001-010. 009 y 010 ya fueron trabajadas en Neon: **no repetirlas allí**. Este conjunto de correcciones no requiere nuevas migraciones.
5. Añadir regresiones y actualizar solo documentación afectada; conservar el diseño CSS y rutas existentes.

## Pruebas locales

No adaptar el código a PHP 8.0 de XAMPP. Usar PHP compatible (por ejemplo `C:/php85/php.exe`) o un entorno aislado.

```powershell
# Sin conexiones a BD
& C:/php85/php.exe backend/tests/ejecutar-pruebas.php
node --experimental-vm-modules backend/tests/test_filtros_listados.mjs

# Clúster nuevo desechable, puertos libres y migraciones SOLO locales
& C:/php85/php.exe backend/tests/ejecutar-pruebas.php --completo --pg-bin "C:/Program Files/PostgreSQL/18/bin"
# Añadir --navegador para recorridos existentes en Edge (Node y Edge requeridos).

& C:/php85/php.exe -l ruta/al/archivo.php
node --check ruta/al/archivo.js
git diff --check
git status --short
```

El modo `--integracion` usa la conexión configurada: no ejecutarlo sin verificar aislamiento. Preferir `--completo`, que fija las conexiones a su propio PostgreSQL local y usa correo simulado. El seed `backend/scripts/cargar-demostracion.php` requiere host local, base `_pruebas`, `APP_ENV=local/test`, puerto explícito y `DEMO_SEED_ENABLE=1`; clave mediante `DEMO_SEED_PASSWORD`. Instrucciones en [la guía de correcciones](docs/uso/correcciones-agenda-demostracion.md).

Informar resultados reales, limitaciones y comprobaciones manuales pendientes. No afirmar que una prueba pasó si no se ejecutó.
