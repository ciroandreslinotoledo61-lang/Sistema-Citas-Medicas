# Actualizar a la version 1.2.0

Este paquete contiene el proyecto completo basado en la version recibida. Mantiene los nombres y capas existentes e incorpora responsabilidades por rol, horarios y citas.

## Si ya tienes una instalacion funcionando

1. Guarda una copia de tu proyecto y una copia de respaldo de PostgreSQL.
2. Integra los archivos del paquete en tu repositorio existente. Conserva tu carpeta .git, backend/.env y frontend/.env. El paquete no incluye esos archivos privados.
3. Si tienes cambios propios posteriores a la version que compartiste, comparalos antes de reemplazar archivos.
4. Deten los dos servidores PHP mientras aplicas la actualizacion.
5. Desde la raiz ejecuta:

```powershell
php backend/scripts/verificar-entorno.php --conexion
php backend/scripts/actualizar-especialidades-medicos.php
php backend/scripts/actualizar-roles-citas.php
```

La migracion 002 se puede repetir si ya estaba aplicada. La 003 agrega campos e indices a las tablas existentes dentro de una transaccion. No borra citas, usuarios o pacientes y no cambia claves. No ejecutes migrar-base-datos.php ni cargar-datos-iniciales.php sobre una instalacion con datos.

6. Inicia de nuevo backend y frontend como indica el README. Conserva APP_TIMEZONE coherente en ambos; la configuracion actual usa America/La_Paz.
7. Recarga el navegador para cargar los archivos JavaScript nuevos. Inicia sesion con cada rol y sigue el recorrido del README.
8. En Administracion crea al menos un horario futuro. No se generan reservas ni horarios ficticios al instalar la actualizacion.
9. Revisa git diff y crea los commits indicados en el README.

La migracion debe aplicarse antes de usar las nuevas rutas de citas. Si falla, no continue la puesta en marcha hasta resolver el mensaje; no recrees la base como solucion.

## Instalacion nueva

Sigue el README completo: crear una base vacia, configurar ambos .env, aplicar migraciones 001, 002 y 003, cargar los datos iniciales y levantar los dos servicios.

## Alcance

El paciente reserva y anula sus propias citas. Recepcion gestiona reservas y asistencia. El medico consulta su agenda y horarios. Administracion gestiona directorios, horarios y operaciones administrativas. Los 42 casos de uso, su prioridad y estado estan en README.md.

Consultas, tratamientos, reportes y edicion de bloques siguen pendientes. Las cuentas iniciales conservan los correos de versiones anteriores; la marca visible sigue siendo Clinica.
