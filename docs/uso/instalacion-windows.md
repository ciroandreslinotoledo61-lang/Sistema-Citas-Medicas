# Instalacion en Windows

Sigue la secuencia de [README](../../README.md) para una base nueva y [ACTUALIZAR](../../ACTUALIZAR-ESTA-VERSION.md) para una existente.

Usa PHP 8.5 de C:\php85 y PostgreSQL 18. `php --ini` muestra el archivo cargado; habilita pdo_pgsql, curl y mbstring si faltan. Verifica `php -m` y `php backend/scripts/verificar-entorno.php`. Agrega C:\Program Files\PostgreSQL\18\bin al PATH para las utilidades SQL. Configura ambos .env y ejecuta las migraciones en orden, deteniendote si alguna falla.

XAMPP puede aportar PHP/Apache, pero PostgreSQL se instala aparte: MySQL y phpMyAdmin no sirven para importar este esquema. Para repetir el entorno documentado usa dos servidores PHP en terminales separadas. Apache requiere sitios/rewrite adaptados a los puntos publicos y assets; no se probo esa configuracion en esta entrega. Nunca sirvas la raiz con .env o src.

| Sintoma | Revisar |
| --- | --- |
| php no reconocido | PATH o `& "C:\php85\php.exe"` |
| could not find driver | pdo_pgsql del PHP/php.ini seleccionado |
| Conexion rechazada | Servicio PostgreSQL, host, puerto y credenciales |
| Error 419 | Recargar CSRF; cookies y mismo host en ambos entornos |
| Vista sin sesion | Ambos servidores activos, API_URL y nombre de cookie |
| Columna inexistente | Base seleccionada y migraciones en orden |
| Sin turnos | Fecha futura, horario generado y medico/especialidad activos |
| Segunda reserva rechazada | Existe una Programada sin resultado |
| Conflicto al guardar consulta | Recargar y revisar version actual |

Codigo/documentacion nueva sin acentos segun la preferencia del proyecto; archivos y datos siguen UTF8. No publiques .env o la clave local del seeder.
