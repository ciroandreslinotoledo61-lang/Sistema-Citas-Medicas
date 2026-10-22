# Citas Medicas - Centro Medico Vida Salud

Sistema web para el proyecto academico de citas medicas, desarrollado con PHP y PostgreSQL.

La organizacion toma como referencia CADEFAR Inventory: backend, frontend y documentacion separados; cada capa tiene una responsabilidad y cada funcion expresa lo que hace. Esta entrega mantiene PHP y PostgreSQL, como pide el proyecto. No requiere Node.js, React, Sequelize, pnpm ni Composer.

## Estado actual: avance 2 (version 1.1.0)

Esta entrega conserva el acceso, usuarios y pacientes de la V1 y agrega la gestion de Especialidades y Medicos. Los dos modulos forman un avance completo para Git.

**Si ya tienes la V1 instalada:** empieza por [Actualizar Especialidades y Medicos](docs/ai/actualizacion-especialidades-medicos.md). Aplica el parche y la migracion 002; conserva tus .env y tus registros. El ZIP de este avance es una actualizacion y requiere la V1.

| Modulo | Incluido hasta este avance |
| --- | --- |
| Autenticacion | Iniciar sesion, cerrar sesion, verificar cuenta activa y controlar inactividad |
| Roles | Administrador, Recepcionista, Medico y Paciente; catalogo fijo |
| Usuarios | Listar, buscar, crear cuentas administrativas, editar nombres/correo y activar/desactivar |
| Pacientes | Registro publico o desde recepcion, busqueda, detalle y actualizacion de contacto |
| Historia clinica | Creacion automatica al registrar un paciente; aun sin edicion clinica |
| Perfil personal | Cada paciente puede consultar solamente su propio perfil |
| Especialidades | Listar, buscar, crear, editar, activar/desactivar y ver cantidad de medicos activos |
| Medicos | Registrar cuenta y perfil, buscar, editar, asignar especialidad y activar/desactivar |
| Base de datos | 11 tablas; migracion 002 con unicidad normalizada de especialidades y matriculas |
| Interfaz | Paginas adaptables a computadora y celular, mensajes de error y paginacion |

**Pendiente para los siguientes avances:** horarios, turnos de 15 minutos, reserva/cancelacion de citas, consultas, tratamientos y reportes. La cuenta Medico puede iniciar sesion, pero su agenda aun no esta implementada.

Todos los nombres de archivos, comentarios, mensajes y textos incluidos se escribieron sin acentos. Los archivos usan UTF-8 sin BOM; los datos ingresados por las personas pueden contener caracteres Unicode.

## 1. Requisitos

| Herramienta | Uso |
| --- | --- |
| PHP 8.2 o posterior compatible | Ejecutar backend y frontend |
| Extensiones PDO, pdo_pgsql, mbstring, curl y session | Base de datos, textos y sesiones |
| PostgreSQL 15 o posterior compatible | Guardar los datos |
| pgAdmin o psql | Crear la base de datos |
| Git | Registrar los avances |
| VS Code y navegador moderno | Editar y revisar el proyecto |

En Windows puedes usar PHP incluido en XAMPP. Para este proyecto se arrancan dos servidores de desarrollo con PHP; no hace falta iniciar MySQL de XAMPP. PostgreSQL se instala y ejecuta por separado.

## 2. Estructura del proyecto

```text
citas-medicas/
|-- backend/
|   |-- .env.example
|   |-- scripts/
|   |   |-- verificar-entorno.php
|   |   |-- migrar-base-datos.php
|   |   |-- cargar-datos-iniciales.php
|   |   `-- actualizar-especialidades-medicos.php
|   |-- public/
|   |   `-- index.php
|   |-- src/
|   |   |-- presentation/
|   |   |   |-- routes/
|   |   |   |-- middlewares/
|   |   |   `-- controllers/
|   |   |-- business/
|   |   |   |-- services/
|   |   |   `-- validators/
|   |   |-- data/
|   |   |   |-- config/
|   |   |   |-- models/
|   |   |   |-- repositories/
|   |   |   |-- migrations/
|   |   |   `-- seeders/
|   |   |-- shared/
|   |   |   |-- config/
|   |   |   |-- constants/
|   |   |   |-- errors/
|   |   |   `-- utils/
|   |   |-- bootstrap.php
|   |   |-- app.php
|   |   `-- server.php
|   `-- tests/
|-- frontend/
|   |-- .env.example
|   |-- public/
|   |   `-- index.php
|   `-- src/
|       |-- api/
|       |-- app/
|       |-- components/
|       |-- features/
|       |   |-- auth/
|       |   |-- inicio/
|       |   |-- panel/
|       |   |-- usuarios/
|       |   |-- pacientes/
|       |   |-- especialidades/
|       |   `-- medicos/
|       |-- layouts/
|       |-- routes/
|       |-- styles/
|       `-- server.php
|-- docs/
|   `-- ai/
|       |-- contexto-proyecto.md
|       |-- arquitectura-y-nombres.md
|       |-- requisitos-y-reglas.md
|       |-- diccionario-datos.md
|       |-- api.md
|       |-- pruebas.md
|       |-- actualizacion-especialidades-medicos.md
|       |-- casos-uso-especialidades-medicos.md
|       `-- plan-avances-git.md
|-- .gitignore
`-- README.md
```

Los archivos de arranque se llaman `app.php` y `server.php` porque este proyecto usa PHP. No se incluyen `package.json` ni `.sequelizerc`: pertenecen al stack de CADEFAR y aqui no se utilizan.

## 3. Preparar el proyecto en Windows

Para una instalacion nueva, extrae la V1 y aplica este avance como explica la guia de actualizacion. Abre la carpeta del proyecto en VS Code y una terminal PowerShell dentro de ella. Si ya funciona tu V1, sigue solamente la guia de actualizacion.

Comprueba PHP:

```powershell
php -v
php --ini
```

Si Windows no reconoce `php`, agrega PHP de XAMPP al PATH de esa terminal:

```powershell
$env:Path += ";C:\xampp\php"
php -v
```

Repite ese ajuste en cada terminal nueva si todavia no lo agregaste al PATH de Windows.

En el archivo `php.ini` indicado por `php --ini`, habilita las extensiones que falten, quitando el `;` inicial. No dupliques las lineas:

```ini
extension=pdo_pgsql
extension=mbstring
extension=curl
```

Guarda y reinicia los procesos PHP que ya estuvieran abiertos. Verifica:

```powershell
php backend/scripts/verificar-entorno.php
```

## 4. Variables de entorno

Desde la raiz del proyecto:

```powershell
Copy-Item backend/.env.example backend/.env
Copy-Item frontend/.env.example frontend/.env
```

En Linux/macOS usa `cp` en lugar de `Copy-Item`.

Edita `backend/.env`:

```dotenv
APP_ENV=local
APP_TIMEZONE=America/La_Paz
FRONTEND_URL=http://127.0.0.1:8080
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=citas_medicas
DB_USER=postgres
DB_PASS=TU_CLAVE_DE_POSTGRESQL
DB_SSLMODE=prefer
SESSION_COOKIE_NAME=citas_medicas_sesion
SESSION_SECURE=false
SESSION_IDLE_SECONDS=1800
SEED_USERS_PASSWORD=TU_CLAVE_DE_PRUEBA_DE_12_O_MAS_CARACTERES
```

Reemplaza los dos valores de clave. `SEED_USERS_PASSWORD` debe tener entre 12 y 72 bytes. El seeder genera hashes con `password_hash`; no guarda esa clave en texto plano en la base.

En `frontend/.env`:

```dotenv
APP_ENV=local
APP_TIMEZONE=America/La_Paz
FRONTEND_URL=http://127.0.0.1:8080
API_URL=http://127.0.0.1:8000/api
SESSION_COOKIE_NAME=citas_medicas_sesion
```

Usa **127.0.0.1 en ambas aplicaciones**. No mezcles `localhost` y `127.0.0.1`, porque las cookies de sesion dependen del host. Las variables del sistema tienen prioridad sobre los archivos `.env`. No hay interpolacion de variables dentro de estos archivos.

## 5. Crear la base de datos

En pgAdmin, conectado a la base `postgres`, ejecuta esta sentencia por separado:

```sql
CREATE DATABASE citas_medicas WITH ENCODING 'UTF8';
```

Luego, desde la raiz del proyecto:

```powershell
php backend/scripts/verificar-entorno.php --conexion
php backend/scripts/migrar-base-datos.php
php backend/scripts/actualizar-especialidades-medicos.php
php backend/scripts/cargar-datos-iniciales.php
```

La migracion crea las 11 tablas dentro de una transaccion. Es una migracion inicial, no un gestor automatico de versiones: si las tablas existen, se detiene sin borrarlas. La migracion 002 agrega dos indices sin eliminar datos y se puede ejecutar nuevamente. Si detecta duplicados equivalentes anteriores, se detiene para que se revisen.

El seeder requiere una base sin usuarios. Crea cuatro roles, tres especialidades, una cuenta por rol, el perfil medico de prueba y un paciente con historia clinica. Una segunda ejecucion se detiene; no reemplaza cuentas ni claves.

### Cuentas de prueba

| Rol | Correo |
| --- | --- |
| Administrador | `administrador@vidasalud.test` |
| Recepcionista | `recepcionista@vidasalud.test` |
| Medico | `medico@vidasalud.test` |
| Paciente | `paciente@vidasalud.test` |

La clave de las cuatro cuentas es el valor que elegiste en `SEED_USERS_PASSWORD`. Son datos ficticios para desarrollo local. Despues de cargar las cuentas, puedes vaciar esa variable en el archivo `.env`.

## 6. Ejecutar backend y frontend

Abre **dos terminales** en VS Code, desde la raiz del proyecto. Deja ambas abiertas.

Terminal 1: backend

```powershell
cd backend
php -S 127.0.0.1:8000 -t public src/server.php
```

Terminal 2: frontend

```powershell
cd frontend
php -S 127.0.0.1:8080 -t public src/server.php
```

| Servicio | Direccion |
| --- | --- |
| Portal | http://127.0.0.1:8080 |
| Inicio de sesion | http://127.0.0.1:8080/iniciar-sesion |
| Estado del backend | http://127.0.0.1:8000/api/estado |

`/api/estado` solo confirma que PHP y el router responden; no prueba la conexion a PostgreSQL. Para la conexion usa `verificar-entorno.php --conexion`.

Los servidores incorporados de PHP son para desarrollo local. El despliegue en nube requerira un servidor web con HTTPS, la carpeta publica correcta y configuracion de sesiones/origenes; no se publica con `php -S`.

## 7. Como revisar el avance actual

1. Ingresa como Administrador y revisa Usuarios.
2. Crea una cuenta de Recepcionista.
3. Edita sus nombres o correo y prueba desactivar/activar esa cuenta.
4. Ingresa como Recepcionista y registra un paciente.
5. Abre su detalle: debe tener un numero de historia clinica.
6. Actualiza su telefono y contacto de emergencia.
7. Cierra sesion e ingresa como ese paciente.
8. Revisa Mi perfil. Esa cuenta no debe poder abrir Usuarios ni Pacientes.
9. Prueba el registro publico con un correo o CI repetido: debe rechazarlo sin crear datos incompletos.

La creacion de usuarios administrativos solo permite Administrador o Recepcionista. Los pacientes se crean desde su modulo. Los medicos se registran desde Medicos; su rol se asigna automaticamente y su cuenta usa el mismo sistema de login. El estado de las cuentas medicas se cambia desde ese modulo para verificar su especialidad.

Para los dos modulos nuevos:

1. Ingresa como Administrador y crea una especialidad.
2. Crea un medico con correo y matricula nuevos, asignandolo a esa especialidad.
3. Edita sus datos y comprueba la busqueda por matricula.
4. Intenta desactivar la especialidad: debe impedirlo mientras tenga medicos activos.
5. Desactiva el medico; su cuenta debe perder acceso.
6. Desactiva la especialidad e intenta reactivar el medico: debe exigir una especialidad activa.
7. Reactiva la especialidad o reasigna el medico a otra activa; luego reactiva su cuenta.
8. Ingresa con otro rol y comprueba que no pueda acceder a los nuevos modulos.

## 8. Pruebas

Desde la raiz:

```powershell
php backend/tests/test_validaciones.php
php backend/tests/test_permisos.php
php backend/tests/test_validaciones_especialidades_medicos.php
```

La prueba de integracion requiere una base PostgreSQL separada:

```powershell
php backend/tests/test_registro_paciente.php
php backend/tests/test_gestion_especialidades_medicos.php
```

Los comandos de integracion se rechazan si `DB_NAME` no termina en `_pruebas`. Sigue [la preparacion de pruebas](docs/ai/pruebas.md) antes de ejecutarlo.

La comprobacion incluida en esta entrega distingue las pruebas ejecutadas de las pendientes. Consulta ese documento para ver los resultados y las limitaciones del entorno de revision.

## 9. Como esta organizado el codigo

```mermaid
flowchart TD
    Vista["Frontend: vista y formulario"] --> Cliente["Cliente API"]
    Cliente --> Ruta["Ruta y middlewares"]
    Ruta --> Controlador["Controller: entrada y respuesta"]
    Controlador --> Servicio["Service: reglas y transacciones"]
    Servicio --> Repositorio["Repository: SQL preparado"]
    Repositorio --> Modelo["Model: datos del dominio"]
    Repositorio --> Base["PostgreSQL"]
```

Ejemplos de nombres que explican su responsabilidad:

| Funcion | Responsabilidad |
| --- | --- |
| `obtenerUsuarioPorId` | Buscar una cuenta por identificador |
| `validarDatosRegistroPaciente` | Validar los datos del formulario |
| `registrarPacienteConHistoriaClinica` | Coordinar usuario, paciente e historia dentro de una transaccion |
| `crearHistoriaClinicaParaPaciente` | Insertar la historia asociada al paciente |
| `actualizarContactoPaciente` | Cambiar telefono, direccion y contacto de emergencia |
| `registrarEspecialidad` | Crear un servicio medico con nombre unico |
| `registrarMedicoConUsuario` | Crear la cuenta y el perfil profesional en una transaccion |
| `cambiarEstadoMedico` | Cambiar el acceso de la cuenta y validar su especialidad |
| `exigirRolPermitido` | Comprobar si la cuenta puede realizar la accion |

Los controladores no contienen SQL. Los repositorios no generan HTML. El frontend nunca recibe claves de PostgreSQL ni consulta la base directamente.

## 10. Guardar este avance en Git

Despues de aplicar la actualizacion y probarla, desde la carpeta de tu repositorio existente:

```powershell
git status
git diff --stat
git add backend frontend docs README.md
git diff --cached --stat
git commit -m "feat: agregar gestion de especialidades y medicos"
git push
```

Revisa los archivos antes del commit; los .env no deben estar preparados. Esta actualizacion no cambia tu remoto ni tu historial. No necesitas ejecutar otra vez git init ni cargar otra vez los datos iniciales.

El [plan de avances](docs/ai/plan-avances-git.md) indica que sigue: horarios medicos y generacion de turnos.

## 11. Problemas frecuentes

| Mensaje o problema | Revision |
| --- | --- |
| `php` no se reconoce | Agrega la carpeta de PHP al PATH |
| Falta `pdo_pgsql` | Edita el php.ini indicado por `php --ini` |
| Error de conexion | Revisa servicio PostgreSQL, DB_HOST, DB_PORT, DB_USER, DB_PASS y DB_NAME |
| El formulario no conecta | Mantiene las dos terminales abiertas y revisa API_URL |
| El origen no esta permitido | FRONTEND_URL debe coincidir exactamente con el origen abierto |
| La sesion no permanece | Usa el mismo host en ambos puertos; SESSION_SECURE=false solo en HTTP local |
| Las tablas ya existen | No repitas la migracion inicial sobre la misma base |
| El seeder ya encuentra usuarios | No lo uses para cambiar claves ni para modificar cuentas existentes |

## Documentacion

- [Contexto del proyecto](docs/ai/contexto-proyecto.md)
- [Arquitectura y convenciones de nombres](docs/ai/arquitectura-y-nombres.md)
- [Requisitos y reglas de negocio](docs/ai/requisitos-y-reglas.md)
- [Diccionario de datos](docs/ai/diccionario-datos.md)
- [Rutas de la API](docs/ai/api.md)
- [Pruebas](docs/ai/pruebas.md)
- [Actualizar Especialidades y Medicos](docs/ai/actualizacion-especialidades-medicos.md)
- [Casos de uso de los nuevos modulos](docs/ai/casos-uso-especialidades-medicos.md)
- [Plan de avances en Git](docs/ai/plan-avances-git.md)

Referencias tecnicas: [PDO PostgreSQL](https://www.php.net/manual/en/ref.pdo-pgsql.php), [sesiones PHP](https://www.php.net/manual/en/features.session.security.management.php) y [servidor de desarrollo PHP](https://www.php.net/manual/en/features.commandline.webserver.php).
