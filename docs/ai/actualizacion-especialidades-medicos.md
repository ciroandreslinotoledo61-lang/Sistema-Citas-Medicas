# Actualizar Especialidades y Medicos

Este es el avance 2 sobre la V1 entregada. Incluye ambos modulos completos para Administrador y conserva el sistema de acceso y pacientes. El siguiente avance sera Horarios y Turnos.

## 1. Preparar la carpeta

El ZIP de actualizacion contiene:

- LEEME-PRIMERO.md: instrucciones iniciales.
- cambios-especialidades-medicos.patch: todos los cambios para aplicar con Git.
- archivos/: copia de los archivos nuevos y modificados.

Extrae el ZIP en una carpeta llamada actualizacion-especialidades-medicos, al lado de tu carpeta actual del proyecto. Abre una terminal dentro de tu proyecto, donde ya estan backend, frontend, docs y .git.

Comprueba el estado:

```powershell
git status
```

Guarda en un commit los cambios propios que quieras conservar antes de aplicar este avance. Los .env siguen ignorados por Git y no forman parte del paquete.

## 2. Aplicar el avance

Desde la carpeta actual de tu proyecto:

```powershell
git apply --check "../actualizacion-especialidades-medicos/cambios-especialidades-medicos.patch"
git apply "../actualizacion-especialidades-medicos/cambios-especialidades-medicos.patch"
git diff --stat
```

El primer comando comprueba que el parche corresponde a tus archivos. Solo ejecuta el segundo si el primero termina sin errores. Si la ubicacion del ZIP extraido es otra, cambia la ruta entre comillas.

Si aparece un error de parche, tus archivos pueden tener cambios respecto a la V1. Compara los archivos afectados con sus copias dentro de archivos/ y conserva tus modificaciones. No uses git reset --hard para resolverlo. Tambien puedes aplicar manualmente los cambios comparando esas copias.

El parche no contiene .env, .git, credenciales ni cambios del remoto. No necesitas crear otra carpeta de proyecto ni ejecutar git init.

## 3. Actualizar la base de datos

Deten los dos servidores locales con Ctrl+C y, desde la raiz del proyecto, ejecuta:

```powershell
php backend/scripts/actualizar-especialidades-medicos.php
```

Se agregan dos indices sobre las tablas existentes. Los nombres de especialidad y las matriculas se comparan sin diferencias de mayusculas y agrupando espacios. La actualizacion se confirma completa o se revierte. Puede ejecutarse nuevamente.

**No ejecutes migrar-base-datos.php ni cargar-datos-iniciales.php sobre tu base ya instalada.** No tienes que borrar ni crear nuevamente citas_medicas.

Si la migracion encuentra duplicados anteriores, se detiene. Puedes identificarlos con estas consultas de solo lectura:

```sql
SELECT lower(regexp_replace(btrim(nombre_especialidad), '[[:space:]]+', ' ', 'g')) AS nombre_equivalente,
       array_agg(id_especialidad ORDER BY id_especialidad) AS identificadores
FROM especialidades
GROUP BY lower(regexp_replace(btrim(nombre_especialidad), '[[:space:]]+', ' ', 'g'))
HAVING count(*) > 1;

SELECT upper(regexp_replace(btrim(matricula_profesional), '[[:space:]]+', ' ', 'g')) AS matricula_equivalente,
       array_agg(id_medico ORDER BY id_medico) AS identificadores
FROM medicos
GROUP BY upper(regexp_replace(btrim(matricula_profesional), '[[:space:]]+', ' ', 'g'))
HAVING count(*) > 1;
```

Revisa el significado de los duplicados antes de corregirlos: no se eliminan ni fusionan automaticamente. Luego repite el comando de actualizacion.

## 4. Iniciar y probar

Terminal del backend:

```powershell
cd backend
php -S 127.0.0.1:8000 -t public src/server.php
```

Terminal del frontend:

```powershell
cd frontend
php -S 127.0.0.1:8080 -t public src/server.php
```

Abre http://127.0.0.1:8080 e ingresa como Administrador.

1. Abre Especialidades y crea Dermatologia, si no existe.
2. Edita su descripcion y comprueba la busqueda.
3. Abre Medicos y registra una cuenta con correo y matricula nuevos.
4. Asigna la especialidad creada. El rol Medico se establece automaticamente.
5. Edita sus datos y comprueba que puede iniciar sesion.
6. Intenta desactivar la especialidad mientras el medico siga activo: debe mostrar un mensaje de conflicto.
7. Desactiva el medico; su cuenta pierde acceso en la siguiente solicitud.
8. Desactiva la especialidad. Intenta activar el medico: debe impedirlo hasta que la especialidad este activa o el medico sea reasignado a otra activa.
9. Prueba con Recepcionista, Medico y Paciente: ninguno puede gestionar estos modulos.
10. Comprueba que el registro de pacientes sigue funcionando.

La agenda y las consultas del medico todavia no forman parte de este avance.

## 5. Ejecutar las pruebas

Desde la raiz:

```powershell
php backend/tests/test_validaciones.php
php backend/tests/test_permisos.php
php backend/tests/test_validaciones_especialidades_medicos.php
```

La prueba test_gestion_especialidades_medicos.php requiere una base separada terminada en _pruebas. Consulta pruebas.md antes de ejecutarla.

## 6. Registrar los dos modulos en Git

Este paquete integra ambos modulos en las rutas, el menu y las reglas de estado. Despues de probarlo, registra un commit conjunto:

```powershell
git status
git diff --stat
git add backend frontend docs README.md
git diff --cached --stat
git commit -m "feat: agregar gestion de especialidades y medicos"
git push
```

Revisa que no se preparen archivos ajenos a este avance. El mensaje del commit describe los dos pasos terminados; los horarios y las citas se registraran en avances posteriores.
