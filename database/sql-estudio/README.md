# SQL educativos PostgreSQL 18

No reemplazan migraciones ni seeder PHP. Crea una BD nueva, vacia y desechable `clinica_sql_pruebas`, verifica `SELECT current_database();` y ejecuta en pgAdmin en este orden:

| Orden | Archivo | Contenido |
| --- | --- | --- |
| 1 | Tablas, relaciones y poblacion de datos.sql | Doce tablas finales, PK/FK/CHECK/indices, funciones/triggers y datos ficticios |
| 2 | Consultas.sql | Diecinueve ejemplos numerados de filtros, JOIN, grupos, HAVING, subconsultas, CTE y ventanas |
| 3 | Procedimientos y triggers.sql | Funciones reales comentadas y procedimiento didactico de integridad |
| 4 | Transacciones.sql | Reserva, cancelacion, reprogramacion, consulta, auditoria y rollback |

Ejemplo desde la raiz en PowerShell:

```powershell
psql -U postgres -d clinica_sql_pruebas -v ON_ERROR_STOP=1 -f "database/sql-estudio/Tablas, relaciones y poblacion de datos.sql"
psql -U postgres -d clinica_sql_pruebas -v ON_ERROR_STOP=1 -f "database/sql-estudio/Consultas.sql"
psql -U postgres -d clinica_sql_pruebas -v ON_ERROR_STOP=1 -f "database/sql-estudio/Procedimientos y triggers.sql"
psql -U postgres -d clinica_sql_pruebas -v ON_ERROR_STOP=1 -f "database/sql-estudio/Transacciones.sql"
```

Scripts mutantes exigen `_pruebas`; el primero exige public sin tablas. Consultas usa una transaccion de solo lectura. Ejemplos clinicos restringidos usan IDs 0 por defecto: adapta exclusivamente a datos ficticios despues de verificar permiso/identidad. SQL directo como propietario no pasa por autorizacion HTTP; un comentario no reemplaza esa autorizacion.

Cuentas SQL llevan marcadores de hash inutilizables para login. Para demostrar la app usa OTRA BD vacia, 001-008 y seeder PHP con tu clave local. No mezcles procedimientos. Los ejercicios mutantes dejan datos y no se garantizan repetibles indefinidamente: usa otra BD nueva para repetir el conjunto.

Sintaxis PostgreSQL: identidad, RETURNING, JSONB y PL/pgSQL; no AUTO_INCREMENT o DELIMITER de MySQL. Los cuatro archivos se ejecutaron con PostgreSQL 18/PGlite y las tres guardias rechazaron nombres fuera de `_pruebas`. La concurrencia entre procesos tiene su test PHP/PDO nativo aparte.
