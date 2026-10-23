# Actualizar a la version 1.3.0

Esta actualizacion parte del ZIP Sistema-Citas-Medicas-main.zip entregado por el usuario. Integra agenda de 30 minutos, perfiles y reportes en las capas existentes.

## Instalacion existente

1. Guarda tus cambios actuales en un commit o una copia completa del proyecto. Conserva .git y ambos .env. Crea tambien un respaldo de PostgreSQL desde pgAdmin (Backup). Los commits no guardan los datos de PostgreSQL.
2. Deten backend y frontend. Aplica el parche siguiendo LEEME-PRIMERO.md del paquete. Usa primero git apply --check; si hay conflicto, compara los archivos afectados y conserva tus cambios, sin forzar la aplicacion.
3. Si ya usas la agenda de la version 1.2 con migraciones 001, 002 y 003, ejecuta SOLO:

```powershell
php backend/scripts/verificar-entorno.php --conexion
php backend/scripts/actualizar-agenda-reportes.php
```

La migracion 004 se ejecuta en una transaccion y es repetible. No borra usuarios, citas, historias ni turnos. Convierte la disponibilidad futura a 30 minutos y actualiza las asistencias previas a Atendida. Las reservas anteriores mantienen sus horas; su intervalo bloquea cualquier nuevo turno que se cruce.

4. Puedes agregar CITA_DURACION_MINUTOS=30 al backend/.env. Si falta, ya se usa 30. APP_TIMEZONE anterior se ignora; ambos servicios usan la hora de Bolivia internamente. No reemplaces tus .env por los ejemplos.
5. Inicia ambos servidores como indica el README y recarga con Ctrl+F5. Revisa Reportes como Administrador, el filtro de medico en Citas y la edicion/estado de Pacientes. Crea un bloque futuro de una hora: debe generar dos turnos.
6. Ejecuta pruebas en una base separada terminada en _pruebas y revisa git diff antes del commit.

Si falla la migracion, se revierte la transaccion. Corrige conexion, permisos o migraciones anteriores antes de iniciar los servidores. No ejecutes migrar-base-datos.php ni cargar-datos-iniciales.php en tu base con usuarios.

## Instalacion nueva

Sigue el README: base vacia, migraciones 001, 002, 003 y 004 en ese orden; despues cargar-datos-iniciales.php. Se conservan las once tablas.

## Regresar a la version anterior

El parche se puede revertir con git apply -R despues de verificarlo con --check. Eso solo revierte archivos. Si ya aplicaste la migracion, restaura tambien el respaldo de PostgreSQL correspondiente antes de usar el codigo anterior. Hazlo con los servidores detenidos. No se incluye una migracion inversa que elimine datos nuevos.
