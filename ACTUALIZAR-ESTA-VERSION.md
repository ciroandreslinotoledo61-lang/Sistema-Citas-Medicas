# Aplicar la refactorización de arquitectura

Esta entrega parte del ZIP `Sistema-Citas-Medicas-prueba-astra-mensual.zip`, versión funcional 1.4.0 con migraciones 001–005. Es el proyecto completo. No incluye `.git`, archivos `.env`, contraseñas personales ni una copia de PostgreSQL.

## Usar una copia separada

1. Detén tus dos servidores PHP con `Ctrl+C`.
2. Extrae `Clinica-Vida-Salud-Refactorizado.zip` en otra carpeta y abre esa carpeta en VS Code.
3. Copia tus archivos `backend/.env` y `frontend/.env` de la instalación actual a las mismas ubicaciones de la copia refactorizada. No los reemplaces por los ejemplos si ya están configurados.
4. Comprueba `CITA_DURACION_MINUTOS=30`, la URL del frontend, `API_URL` y los datos de PostgreSQL.
5. Si tu base ya tiene 001–005, **esta refactorización no requiere ejecutar migraciones ni el seeder**. Puede usar la misma configuración de conexión; probarla sobre un respaldo de la base permite comparar sin alterar tu trabajo diario.
6. Desde la raíz de la copia ejecuta:

```powershell
& "C:\php85\php.exe" backend/scripts/verificar-entorno.php --conexion
& "C:\php85\php.exe" backend/tests/ejecutar-pruebas.php
```

Terminal 1:

```powershell
& "C:\php85\php.exe" -S 127.0.0.1:8000 -t backend/public backend/src/server.php
```

Terminal 2:

```powershell
& "C:\php85\php.exe" -S 127.0.0.1:8080 -t frontend/public frontend/src/server.php
```

Abre <http://127.0.0.1:8080> y recorre Administración, Recepción, Médico y Paciente. Las instrucciones para una base completamente nueva siguen en el README. La migración inicial y el seeder no se repiten sobre una base que ya contiene cuentas.

## Incorporar los cambios a tu repositorio existente

La carpeta extraída no contiene el historial Git de tu repositorio. Conserva tu carpeta `.git` y tu rama actual. No ejecutes `git init` ni hagas un cambio de rama para instalar este código.

Consulta `docs/refactorizacion/ARCHIVOS-MODIFICADOS.md` y compara los archivos con VS Code. Integra las modificaciones y añade los nuevos archivos indicados. Es importante copiar también `presentation/http`, las rutas por módulo, los nuevos validadores, `DisponibilidadService`, `DisponibilidadController`, `TurnoRepository` y `Transaccion`; copiar únicamente los servicios dejaría referencias incompletas.

Si tienes cambios posteriores al ZIP adjunto, compáralos antes de reemplazar un archivo. Ningún archivo original fue eliminado. No se hicieron commits ni push desde esta entrega.

```powershell
git status --short
git diff --check
git diff --stat
```

## Pruebas con datos

`backend/tests/ejecutar-pruebas.php --integracion` exige una base separada cuyo nombre termine en `_pruebas`, con las cinco migraciones y datos iniciales. No apunta automáticamente a otra base: debes configurarla mediante `DB_NAME` y sus credenciales.

La guía detallada y los resultados están en `docs/refactorizacion/VERIFICACION.md`. La antigua guía de actualización de 1.3 a 1.4 quedó preservada como documento histórico en `docs/refactorizacion/ACTUALIZACION-1.4-HISTORICA.md`.
