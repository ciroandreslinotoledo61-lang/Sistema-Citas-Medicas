# Informe final: privacidad, credenciales y experiencia del paciente

Revision completada el 2026-11-03 sobre `integracion-credenciales-render`.
Ultimo commit: `2e6233d`. Los cambios siguen locales, sin commit ni push.
Se conservaron los 27 archivos modificados y 15 nuevos del primer informe.
No se creo otra rama ni otro proyecto, no se modifico Neon, no se desplego ni
reactivo Render, y no se cambiaron variables privadas ni migraciones 001-009.

Las funcionalidades pendientes estan implementadas y verificadas en el entorno
local autorizado. No quedan fallos en la ejecucion final. El envio real por
Resend y las comprobaciones del entorno publicado quedan pendientes por las
restricciones del encargo, no por falta de implementacion local.

La revision posterior de herramientas traslada las pruebas HTTP y de
configuracion a PHP. Los comandos siguientes ya usan ese ejecutor. Sus
equivalencias y la verificacion de la sustitucion se registran por separado en
[pruebas PHP](pruebas-php.md); los resultados funcionales del 2026-11-03 no
sustituyen la ejecucion de los nuevos archivos.

## Funcionalidades completadas

### Privacidad clinica

- Historial del paciente sin buscador de otras personas ni editor clinico.
- Identidad obtenida de la sesion; manipulacion de IDs y filtros rechazada o
  limitada al registro propio. Historias, consultas y citas tienen alcance SQL.
- Medico restringido a pacientes con relacion de atencion mediante cita
  Programada o Atendida; edicion de consultas propias y antecedentes autorizados.
- Lecturas clinicas auditadas con referencias y actor, sin contenido sensible.
- Administrador y recepcionista siguen sin diagnosticos, tratamientos ni
  antecedentes. La gestion administrativa de cuentas y citas se conserva.

No se confirmo un IDOR explotable en la version inicial: ya existia validacion
backend de propiedad. Se corrigio la interfaz impropia y se reforzo la
proteccion directamente en SQL. No se amplian permisos clinicos administrativos.
El requisito inicial de acceso administrativo autorizado queda recogido en la
[propuesta de permisos](propuesta-permisos-clinicos.md), para revision futura:
permiso independiente, ambito, autorizador, vencimiento, revocacion y auditoria.
La propuesta no crea permisos efectivos, tablas ni rutas nuevas.

### Recuperacion y restablecimiento de contrasenas

- Dos endpoints POST de recuperacion, con CSRF y politica de origen. Son
  publicos porque permiten recuperar acceso sin una sesion previa.
- Tokens de 256 bits aleatorios; se guarda solo SHA-256, vencen a los 30 minutos
  y se consumen una sola vez. La identidad nunca procede de un ID del formulario.
- Una nueva solicitud revoca la anterior. Cambiar correo, contrasena, version
  de credenciales o estado de cuenta invalida enlaces anteriores.
- Confirmar cambia la clave y consume el token atomicamente, revoca las sesiones
  anteriores y no inicia sesion automaticamente.
- Limites persistentes por cuenta, token e IP; mensaje generico para cuentas
  existentes, desconocidas e inactivas. Fallos del proveedor no revelan cuentas.
- Enlace con token en fragmento, retirado de la barra e historial del navegador;
  token enviado solo en cuerpo POST, nunca en ruta, query ni almacenamiento web.
- Correo real mediante Resend/cURL configurable. Las pruebas inyectan un buzon
  simulado desde un router privado CLI; ninguna solicitud HTTP puede habilitarlo.
- Cambio personal para los cuatro roles y restablecimiento administrativo
  existente conservados: temporal de 30 minutos, uso unico, cambio obligatorio,
  revocacion de sesiones, limites y bitacora. El permiso usuarios.gestionar
  incluye otras cuentas administrativas activas; la cuenta propia usa cambio
  personal y las cuentas inactivas no se restablecen.

### Mis citas del paciente

Titulo Mis citas medicas, descripcion Consulta y administra tus reservas,
pestanas Proximas citas e Historial, tarjetas con especialidad, medico, fecha,
hora y estado. Incluye Reservar cita, Ver detalles y Cancelar cita cuando el
servidor lo permite; la cancelacion reutiliza la confirmacion existente.
Historial con fecha y busqueda sencilla de medico/especialidad, sin busqueda de
pacientes. Filtros y paginacion se aplican antes de LIMIT/OFFSET en PostgreSQL;
el historial muestra primero las citas mas recientes.

Se conservan las tablas y herramientas de administrador, recepcion y medico.
No se agrega un limite diario: sigue la regla ya existente de una Programada
activa por paciente. Se mantienen turnos de 30 minutos, cruces, restricciones
antes del inicio, estados y permisos de reprogramacion.

### Textos y codificacion

Se corrigieron textos visibles, errores HTTP, formularios, navegacion,
validaciones, titulos, etiquetas de roles y bitacora. Las etiquetas dinamicas
se traducen para presentar los codigos sin alterar identificadores ni datos
almacenados; el contenido clinico y los nombres introducidos por usuarios no
se reescriben. Los codigos, rutas, nombres de archivo y variables siguen ASCII.

HTML, JS/CSS y JSON usan UTF-8; la conexion PostgreSQL fija client_encoding UTF8.
Todos los archivos fuente revisados son UTF-8 sin BOM ni caracteres de reemplazo.
Los 23 Markdown revisados usan ASCII segun la convencion solicitada.

## Errores corregidos

- Test de arquitectura limitado a contar 58 rutas: ahora compara los 60 contratos
  revisados (metodo, ruta, controlador, accion, roles y argumentos), mantiene
  unicidad/firmas y comprueba superficie publica y prohibiciones clinicas.
- Buscador administrativo presentado al paciente en historial clinico.
- Lecturas SQL que cargaban un registro antes de comprobar su alcance.
- Pantalla de citas del paciente compartida con la tabla administrativa.
- Ausencia de pruebas integrales de recuperacion, concurrencia y migracion 010.
- Textos de interfaz sin tildes y etiquetas tecnicas mostradas directamente.
- Informe anterior adelantado: describia tarjetas como hechas cuando aun no
  existian. Este informe refleja exclusivamente el estado comprobado actual.

## Pruebas funcionales ejecutadas el 2026-11-03

Entorno: Windows, PHP 8.5.11 con pdo_pgsql/cURL, PostgreSQL 18 local,
Node 24 y Edge headless. Datos sinteticos; ningun acceso a Neon.

| Prueba | Resultado final |
| --- | --- |
| Suites PHP | 22 archivos, todos aprobados |
| Privacidad HTTP | 111 comprobaciones aprobadas |
| Recuperacion HTTP con correo simulado | 214 comprobaciones aprobadas |
| Autenticacion y restablecimiento HTTP | 330 comprobaciones aprobadas |
| Contrato HTTP anterior | 137 comprobaciones aprobadas |
| Edge: paciente, citas y formularios por rol | 35 comprobaciones aprobadas |
| Edge: recuperacion, nueva clave e inicio de sesion | 31 comprobaciones aprobadas |
| Configuracion de despliegue sin BD | 10 casos aprobados |
| Sintaxis PHP | 189 archivos sin errores |
| Sintaxis JavaScript/MJS | 43 archivos sin errores |
| Sintaxis del ejecutor PHP posterior | Verificacion registrada por separado en [pruebas PHP](pruebas-php.md) |
| UTF-8/BOM y Markdown ASCII | Sin errores; 23 Markdown |
| git diff --check | Sin errores de espacios |

Dentro de las 22 suites: 63 comprobaciones clinicas/auditoria, 53 de citas
propias y paginacion, 14 de rollback/concurrencia de recuperacion, 15 de
migracion 010 y su script, 29 de validadores de recuperacion y las regresiones
anteriores de agenda, reservas concurrentes, credenciales y transacciones.
Dos procesos intentaron consumir el mismo token: solo uno tuvo exito.
Un fallo de auditoria inducido revirtio token, contrasena y version juntos.

Los casos HTTP verifican token valido, invalido, vencido, usado y reemitido;
cambio de correo, cuenta desactivada, cambio personal y reset administrativo;
CSRF, origen ajeno, IDs manipulados, limites persistentes y sesiones revocadas.
Se comprueban logs sin credenciales ni tokens. El buzon de pruebas contiene
solo los enlaces sinteticos necesarios para probar el flujo y no es un log de
produccion ni un servicio habilitable desde la API.

Navegador real con PHP/API/PostgreSQL reales, solo el transporte de correo
simulado. Se verificaron vistas vacias, reserva y cancelacion por formulario,
tarjetas, historial/filtros, formularios de recuperacion, validaciones, limpieza
del token, login con la nueva clave y rechazo al reutilizar el enlace.
Sin excepciones JavaScript ni desbordamiento horizontal a 320, 390 y 1440 px.
Tambien se inspeccionaron capturas de movil y escritorio.

Comandos reproducibles:

```powershell
& C:/php85/php.exe backend/tests/ejecutar-pruebas.php
& C:/php85/php.exe backend/tests/ejecutar-pruebas.php --completo --pg-bin "C:/Program Files/PostgreSQL/18/bin" --navegador
& C:/php85/php.exe deploy/tests/test_configuracion.php
```

El runner PHP crea sus propias bases locales y detiene servidores y PostgreSQL
al finalizar. No requiere Python. Artefactos historicos del 2026-11-03,
excluidos de Git (pueden retirarse en la limpieza de temporales):

- `.tmp-auth/ejecucion-61e214d725b3`
- `.tmp-auth/navegador-082d3e56-7910-44dd-a23b-7bf85cfbb3bd`
- `.tmp-auth/navegador-recuperacion-92258774-5aa7-4c37-b838-5fb96a956165`

Fallos finales: ninguno. El fallo previo por 58 rutas quedo corregido; los
resultados anteriores no se usan como sustituto de esta ejecucion completa.

## Migracion 010

Unica migracion nueva: `backend/src/data/migrations/010_recuperacion_contrasena.sql`.
Crea recuperaciones_contrasena, con referencia a usuario, token/correo como
hash, version de credenciales, creacion, vencimiento y marca de consumo.
No cambia contrasenas, usuarios ni contenido clinico existente.

Se comprobo sobre una base local con 001-009 y datos existentes, usando tambien
`backend/scripts/actualizar-recuperacion-contrasena.php --aplicar`. Se compararon
usuarios, credenciales y tablas clinicas antes/despues; permanecen iguales.
Se verificaron unicidad, formato hash, UTF-8, vista previa sin conexion, rechazo
de configuracion externa/production y rechazo de una segunda aplicacion.
Las migraciones 001-009 se compararon con 2e6233d y permanecen intactas.

Para una copia local previamente preparada con 001-009:

```powershell
& C:/php85/php.exe backend/scripts/actualizar-recuperacion-contrasena.php
& C:/php85/php.exe backend/scripts/actualizar-recuperacion-contrasena.php --aplicar
```

El script solo admite PostgreSQL local y DB_NAME terminado en _pruebas.
No se aplico 010 en Neon; el arranque de Render tampoco la aplica automaticamente.

## Resend: configuracion y verificaciones externas pendientes

| Variable | Configuracion necesaria |
| --- | --- |
| MAIL_PROVIDER | resend |
| RESEND_API_KEY | Clave privada real con permiso de envio, solo en entorno seguro |
| MAIL_FROM | Direccion de remitente de un dominio verificado |
| FRONTEND_URL | Origen HTTPS publico de la clinica, sin ruta ni query |

No se enviaron correos reales ni se inventaron credenciales. Las claves reales
no son necesarias para ejecutar las pruebas locales. El operador debera:

1. Verificar dominio/remitente y DNS en Resend, configurar sus variables privadas.
2. Desactivar seguimiento de enlaces/aperturas para recuperacion y evitar
   analitica o registro de cuerpos POST con tokens.
3. Probar entrega real a una cuenta controlada, bandeja/spam, enlace HTTPS,
   vencimiento y segundo uso. Estas pruebas requieren Resend real y no se hicieron.
4. Revisar respuestas reales del proveedor, TLS y conectividad desde el entorno
   publicado. Las pruebas actuales simulan tambien fallos de envio.

Referencia del proveedor: [envio de correo](https://resend.com/docs/api-reference/emails/send-email).

## Recorrido manual local

1. Paciente A: Mi historial clinico, sin buscador ni editor; abrir una consulta
   propia. Paciente B debe recibir rechazo al pedir el ID clinico de A.
2. Medico: consultar paciente asignado y verificar rechazo del no asignado.
   Admin y recepcion siguen sin acceso clinico; admin conserva bitacora/cuentas.
3. Paciente: abrir Mis citas medicas, reservar, ver detalles, cancelar antes del
   inicio y comprobar Historial. Filtrar fecha/medico; probar movil y escritorio.
4. Cada rol: cambiar contrasena personal. Admin: restablecer otra cuenta activa,
   entrar con temporal y completar cambio obligatorio; segundo uso rechazado.
5. Ejecutar el runner con --navegador para el recorrido completo de recuperacion
   con buzon simulado. El servidor normal sin correo configurado muestra error
   de disponibilidad; no simula un envio exitoso.

## Preparacion de publicacion

El estado local esta listo para preparar la publicacion. No equivale a una
validacion del servicio publicado ni autoriza ejecutarla en este encargo.
Antes de publicar, obtener respaldo verificado y probar en copia aislada;
aplicar SOLO 010 una vez sobre Neon con 001-009, en una transaccion controlada
por el operador, y publicar despues el codigo revisado. No repetir seeder ni
migraciones anteriores. Configurar correo, HTTPS y cookies seguras y comprobar
sesion/CSRF/permisos en el entorno real. Revisar REMOTE_ADDR tras el proxy de
Render para que los limites de intentos reflejen la politica deseada.

El acceso clinico administrativo sigue siendo una propuesta pendiente de
revision; no debe habilitarse como parte accidental del despliegue.
Ninguna operacion sobre Neon o Render fue ejecutada durante este trabajo.

## Inventario funcional anterior al traslado de pruebas a PHP

El informe del 2026-11-03 registro 119 archivos existentes modificados y 27
nuevos. La mayor parte de los cambios adicionales corresponde a textos
visibles; se conserva la arquitectura. El inventario siguiente conserva ese
registro historico; los ejecutores sustituidos y los nuevos soportes PHP se
detallan en [pruebas PHP](pruebas-php.md).

Archivos existentes modificados:

```text
DESPLEGAR-REPOSITORIO-ACTUAL.md
README.md
REVISION-DESPLIEGUE.md
backend/.env.example
backend/src/app.php
backend/src/business/services/AutenticacionService.php
backend/src/business/services/BitacoraService.php
backend/src/business/services/CitaService.php
backend/src/business/services/ConsultaService.php
backend/src/business/services/CredencialService.php
backend/src/business/services/EspecialidadService.php
backend/src/business/services/HistoriaClinicaService.php
backend/src/business/services/HorarioMedicoService.php
backend/src/business/services/MedicoService.php
backend/src/business/services/PacienteService.php
backend/src/business/services/UsuarioService.php
backend/src/business/validators/BitacoraValidator.php
backend/src/business/validators/CitaValidator.php
backend/src/business/validators/ConsultaValidator.php
backend/src/business/validators/EspecialidadValidator.php
backend/src/business/validators/HorarioMedicoValidator.php
backend/src/business/validators/MedicoValidator.php
backend/src/business/validators/PacienteValidator.php
backend/src/business/validators/UsuarioValidator.php
backend/src/business/validators/ValidarCamposFormulario.php
backend/src/data/config/ConexionPostgresql.php
backend/src/data/models/Rol.php
backend/src/data/models/Usuario.php
backend/src/data/repositories/CitaRepository.php
backend/src/data/repositories/ConsultaRepository.php
backend/src/data/repositories/HistoriaClinicaRepository.php
backend/src/data/repositories/IntentoAutenticacionRepository.php
backend/src/presentation/controllers/AutenticacionController.php
backend/src/presentation/controllers/CitaController.php
backend/src/presentation/controllers/ConsultaController.php
backend/src/presentation/controllers/CredencialController.php
backend/src/presentation/controllers/EstadoController.php
backend/src/presentation/controllers/HorarioMedicoController.php
backend/src/presentation/controllers/MedicoController.php
backend/src/presentation/controllers/PacienteController.php
backend/src/presentation/http/EnrutadorApi.php
backend/src/presentation/http/FabricaControladores.php
backend/src/presentation/middlewares/VerificarOrigenSolicitud.php
backend/src/presentation/middlewares/VerificarRolUsuario.php
backend/src/presentation/middlewares/VerificarTokenCsrf.php
backend/src/presentation/routes/RutasAutenticacion.php
backend/src/shared/config/CargarVariablesEntorno.php
backend/src/shared/config/ConfiguracionAgenda.php
backend/src/shared/constants/PermisosSistema.php
backend/src/shared/constants/RolesSistema.php
backend/src/shared/utils/SolicitudHttp.php
backend/tests/autenticacion_aislada.php
backend/tests/ejecutar-pruebas.php
backend/tests/test_arquitectura.php
backend/tests/test_autenticacion_http.py
backend/tests/test_consultas_bitacora.php
docs/uso/usuarios-autenticacion.md
frontend/src/api/ClienteApiServidor.php
frontend/src/api/cliente-api.js
frontend/src/app/configuracion.php
frontend/src/app/rutas.php
frontend/src/components/campos-contacto.php
frontend/src/components/campos-personales-paciente.php
frontend/src/components/campos-usuario.php
frontend/src/components/contrasenas.js
frontend/src/components/formularios.js
frontend/src/components/interfaz.js
frontend/src/components/lista-paginada.js
frontend/src/components/modulos-disponibles.php
frontend/src/components/navegacion.php
frontend/src/components/paginacion.php
frontend/src/features/auth/autenticacion-api.js
frontend/src/features/auth/cambiar-contrasena.js
frontend/src/features/auth/cambiar-contrasena.php
frontend/src/features/auth/iniciar-sesion.php
frontend/src/features/bitacora/bitacora.js
frontend/src/features/bitacora/bitacora.php
frontend/src/features/citas/detalle-cita.js
frontend/src/features/citas/detalle-cita.php
frontend/src/features/citas/formulario-cita.js
frontend/src/features/citas/formulario-cita.php
frontend/src/features/citas/lista-citas.php
frontend/src/features/citas/listar-citas.js
frontend/src/features/citas/presentacion-citas.js
frontend/src/features/consultas/detalle-consulta.js
frontend/src/features/consultas/detalle-consulta.php
frontend/src/features/consultas/formulario-consulta.js
frontend/src/features/consultas/formulario-consulta.php
frontend/src/features/consultas/historial-clinico.php
frontend/src/features/consultas/lista-consultas.php
frontend/src/features/consultas/listar-consultas.js
frontend/src/features/especialidades/formulario-especialidad.php
frontend/src/features/especialidades/lista-especialidades.php
frontend/src/features/especialidades/listar-especialidades.js
frontend/src/features/horarios/formulario-horario.js
frontend/src/features/horarios/formulario-horario.php
frontend/src/features/horarios/lista-horarios.php
frontend/src/features/inicio/error.php
frontend/src/features/inicio/inicio.php
frontend/src/features/medicos/formulario-medico.js
frontend/src/features/medicos/formulario-medico.php
frontend/src/features/medicos/lista-medicos.php
frontend/src/features/medicos/listar-medicos.js
frontend/src/features/pacientes/detalle-paciente.js
frontend/src/features/pacientes/detalle-paciente.php
frontend/src/features/pacientes/formulario-registro.php
frontend/src/features/pacientes/lista-pacientes.php
frontend/src/features/pacientes/registrar-paciente.js
frontend/src/features/panel/panel.php
frontend/src/features/reportes/reportes.js
frontend/src/features/reportes/reportes.php
frontend/src/features/usuarios/formulario-usuario.php
frontend/src/features/usuarios/lista-usuarios.php
frontend/src/features/usuarios/listar-usuarios.js
frontend/src/features/usuarios/restablecer-contrasena.js
frontend/src/features/usuarios/restablecer-contrasena.php
frontend/src/layouts/principal.php
frontend/src/routes/ProteccionVista.php
frontend/src/styles/estilos.css
```

Archivos nuevos:

```text
backend/scripts/actualizar-recuperacion-contrasena.php
backend/src/business/services/CorreoTransaccionalService.php
backend/src/business/services/RecuperacionContrasenaService.php
backend/src/data/migrations/010_recuperacion_contrasena.sql
backend/src/data/repositories/RecuperacionContrasenaRepository.php
backend/src/presentation/controllers/RecuperacionContrasenaController.php
backend/tests/CorreoPrueba.php
backend/tests/contratos/rutas.json
backend/tests/privacidad_aislada.php
backend/tests/recuperacion_aislada.php
backend/tests/servidor_recuperacion_aislado.php
backend/tests/test_migracion_010.php
backend/tests/test_paciente_citas.php
backend/tests/test_paciente_navegador.mjs
backend/tests/test_privacidad_http.py
backend/tests/test_recuperacion_http.py
backend/tests/test_recuperacion_navegador.mjs
backend/tests/test_recuperacion_transacciones.php
backend/tests/test_recuperacion_validacion.php
docs/uso/actualizacion-privacidad-recuperacion.md
docs/uso/propuesta-permisos-clinicos.md
frontend/src/features/auth/confirmar-recuperacion.js
frontend/src/features/auth/confirmar-recuperacion.php
frontend/src/features/auth/recuperar-contrasena.js
frontend/src/features/auth/recuperar-contrasena.php
frontend/src/features/citas/citas-paciente.js
frontend/src/features/citas/citas-paciente.php
```
