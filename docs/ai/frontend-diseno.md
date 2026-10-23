# Frontend: rediseno de Clinica

## Alcance

Actualizacion visual para la V1 con el avance de Especialidades y Medicos ya aplicado. El nombre visible es Clinica, con Centro medico como descripcion. Conserva PHP, HTML, CSS y JavaScript sin frameworks ni dependencias externas.

El avance visual inicial conservo backend, migraciones, archivos .env y credenciales. La version 1.3 agrega la migracion 004 y cambios funcionales; consulta ACTUALIZAR-ESTA-VERSION.md. Los correos de prueba creados por el seeder siguen siendo los mismos, aunque contengan el nombre anterior. No hay que volver a cargar datos.

## Criterio visual

- Fondo claro, superficies blancas y un solo color principal: verde oscuro.
- Tipografia del sistema, disponible sin descargar fuentes.
- Bordes discretos, espacios consistentes y sombras minimas.
- Iconos SVG locales; sin emojis, fotografias generadas ni recursos de un CDN.
- Textos breves, sin acentos, y acciones con nombres concretos.
- Tablas orientadas al trabajo diario, con buscador, estados y paginacion.
- Panel con accesos a funciones existentes; sin estadisticas ni actividad inventadas.

## Organizacion

| Archivo | Responsabilidad |
| --- | --- |
| frontend/src/layouts/principal.php | Estructura comun, titulo, contenido y pie de pagina |
| frontend/src/components/navegacion.php | Cabecera publica y menu lateral segun el rol |
| frontend/src/components/modulos-disponibles.php | Metadatos compartidos entre menu y panel |
| frontend/src/components/iconos.php | Iconos SVG pequenos y reutilizables |
| frontend/src/components/interfaz.js | Menu movil, manejo de foco y mostrar/ocultar clave |
| frontend/src/components/campos-usuario.php | Campos de acceso reutilizados por los formularios |
| frontend/src/components/campos-contacto.php | Campos de contacto y ayudas |
| frontend/src/components/paginacion.php | Controles comunes de las tablas |
| frontend/src/styles/estilos.css | Variables, estructura, componentes y adaptacion responsive |
| frontend/src/features/ | Vistas y funcionalidades de cada modulo |

Los metadatos del menu son presentacion; no reemplazan los permisos. ProteccionVista, las rutas del frontend y los middlewares del backend siguen siendo responsables de controlar el acceso.

El unico cambio en rutas.php es el titulo de la pagina de inicio. No se agregaron endpoints ni se cambiaron roles permitidos.

## Funcionalidades conservadas

| Area | Comportamiento |
| --- | --- |
| Autenticacion | Mismos campos, token CSRF, cookies, inicio y cierre de sesion |
| Redireccion | El login sigue enviando a /panel; el contenido depende del rol |
| Usuarios | Listar, buscar, crear, editar y cambiar estado con las restricciones existentes |
| Pacientes | Registro publico o desde recepcion, busqueda, detalle y actualizacion de contacto |
| Especialidades | Registro, edicion, busqueda y activacion/desactivacion |
| Medicos | Cuenta y perfil, matricula, especialidad y estado |
| Paginacion | Conserva busqueda, pagina y hayPaginaSiguiente de la API existente |
| Errores | Conserva los mensajes del servidor y el enlace de acceso cuando vence la sesion |

Todos los archivos *-api.js, cliente-api.js, ClienteApiServidor.php, ProteccionVista.php y los scripts de negocio de cada modulo permanecen iguales a la base recibida. aplicacion.js incorpora la inicializacion visual y permite ver el error de cierre de sesion fuera del menu movil.

## Roles y modulos futuros

| Rol | Accesos visibles actuales |
| --- | --- |
| Administrador | Panel, Pacientes, Medicos, Especialidades y Usuarios |
| Recepcionista | Panel y Pacientes |
| Medico | Panel con su cuenta y aviso de agenda aun no disponible |
| Paciente | Panel y Mi perfil, sin formulario de edicion de contacto |

Este apartado describe el avance visual inicial. En la version 1.3, horarios, citas y reportes ya estan implementados; consultas y tratamientos siguen pendientes. No se agregaron botones sin destino ni solicitudes para esos modulos. Cuando existan sus rutas y permisos, sus metadatos se pueden incorporar a modulos-disponibles.php y sus vistas a features/.

## Adaptacion y accesibilidad

- Menu lateral permanente en escritorio y desplegable hasta 960 px.
- Apertura y cierre con botones, clic fuera y tecla Escape.
- El menu movil mantiene el foco dentro de sus controles y lo devuelve al cerrar.
- El contenido de fondo queda inactivo mientras el menu esta abierto.
- Sin JavaScript, la navegacion permanece visible y se muestra el aviso existente.
- Tablas con desplazamiento horizontal dentro de su propio contenedor.
- Campos con etiquetas, indicacion de obligatorios y autocompletado conservado.
- Enlaces de salto, foco visible, mensajes de estado y respeto por movimiento reducido.

## Comprobacion de esta entrega

Se verificaron la sintaxis de los 26 archivos PHP del frontend con PHP 8.2.33 y PHP 8.5.10 en WebAssembly, y la sintaxis de los 20 archivos JavaScript. El backend se comparo byte a byte con la V1 mas la actualizacion recibida: sin cambios.

La revision de navegador usa vistas renderizadas desde los archivos PHP reales y respuestas de prueba que siguen los contratos existentes. Estas respuestas y cuentas no forman parte del paquete instalable ni se guardan en tu base.

En Chromium se revisaron 21 vistas a 1440, 390 y 320 px (63 combinaciones) sin desbordamientos de pagina. Se comprobo el menu movil con teclado, las opciones de cada rol, el login, busqueda, paginacion, ausencia de resultados, errores, edicion, confirmaciones de estado y cierre de sesion con respuestas de prueba. No se detectaron errores de JavaScript ni bloqueos de la politica CSP.

La comprobacion con PostgreSQL real, Apache/XAMPP y tus credenciales queda pendiente en tu entorno. No se presenta esta revision visual como una prueba de integracion de base de datos.

## Aplicar y registrar en Git

Sigue LEEME-PRIMERO.md del ZIP. Este avance no incluye .env, .git, node_modules ni herramientas de prueba. No requiere instalar Node.js para ejecutarse.

Despues de revisar los cambios, puedes guardar dos commits desde tu repositorio existente:

```powershell
git add frontend
git diff --cached --stat
git commit -m "feat: renovar interfaz de clinica y navegacion por rol"
git add docs/ai/frontend-diseno.md
git diff --cached --stat
git commit -m "docs: documentar estructura y rediseno del frontend"
```

Revisa git status antes de preparar archivos si tienes otros cambios pendientes. El ZIP no sube cambios a GitHub. Ejecuta git push desde tu repositorio cuando quieras publicar tus commits.

## Responsabilidades y agenda en 1.2.0

Se conservan la marca Clinica, los componentes visuales y el menu acordeon recibido. El panel ofrece solamente acciones del rol. Citas y Horarios usan los mismos estilos, tablas, paginacion y formularios que el resto de modulos.

- Paciente: Mis citas, Reservar cita y Mi perfil. Seleccion de especialidad, medico, fecha y turno; anulacion con confirmacion en el detalle.
- Recepcionista: pacientes, reserva para un paciente buscado, listado de citas, reprogramacion y asistencia.
- Medico: Mi agenda y Mis horarios; detalle con datos basicos del paciente asignado, sin controles administrativos.
- Administrador: directorios, citas y configuracion de bloques/turnos.

Rutas nuevas de vistas: /citas, /citas/nueva, /citas/{id}, /citas/{id}/reprogramar, /horarios, /horarios/nuevo. ProteccionVista comprueba rol; las respuestas de la API comprueban tambien propiedad. Las acciones del detalle se calculan desde puedeCancelar, puedeReprogramar y los permisos de asistencia.

Los turnos se presentan como radios con etiqueta y foco de teclado. Cambiar medico, especialidad o fecha descarta la seleccion anterior; un contador evita pintar respuestas antiguas sobre la seleccion actual. Un conflicto de reserva actualiza los turnos. Las tablas mantienen desplazamiento horizontal dentro de su contenedor en celular.

## Actualizacion 1.3

Reportes usa la paleta central, filtros responsive y tablas desplazables. Pacientes permite edicion completa y estado; Medicos incluye telefono. La agenda muestra inicio y fin del turno, sin identificadores de zona. Ver actualizacion-agenda-reportes.md y pruebas.md para el alcance actual.
