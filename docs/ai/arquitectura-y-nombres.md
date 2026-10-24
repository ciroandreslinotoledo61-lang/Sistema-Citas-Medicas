# Arquitectura y nombres vigentes

Actualizada el 24 de octubre de 2026. [Guía completa y ejemplo de registro de paciente](../../GUIA-ARQUITECTURA-Y-DEFENSA.md).

| Ubicación | Responsabilidad |
| --- | --- |
| `presentation/routes` | Define endpoints, métodos, controlador, acción y requisitos de rol. `RutasApi::definir()` reúne los módulos. |
| `presentation/http` | `EnrutadorApi` resuelve el endpoint y ejecuta middleware; `FabricaControladores` compone dependencias; `RutaHttp` describe la ruta. |
| `presentation/middlewares` | Origen, CSRF, sesión y rol general. |
| `presentation/controllers` | Lee datos HTTP, delega en Services y devuelve JSON. |
| `business/services` | Casos de uso, coordinación de validadores y repositorios, permisos de negocio, alcance propio, generación de intervalos y límites de las transacciones. |
| `business/validators` | Formatos, tipos, filtros y condiciones de la entrada por módulo. |
| `data/repositories` | Consultas SQL, parámetros, bloqueos y persistencia. No decide permisos ni genera intervalos. |
| `data/models` | Entidades PHP y representación pública, sin ORM. |
| `data/config` | PDO y ejecución de transacciones con el aislamiento solicitado por el servicio. |
| `shared` | Configuración, constantes, errores y utilidades. |

Se mantienen nombres de clase en PascalCase y métodos/variables en camelCase descriptivo en español. Las carpetas usan minúsculas, de acuerdo con el cargador de `bootstrap.php`; los archivos de clases mantienen su nombre exacto. El frontend conserva módulos JavaScript ES y vistas por feature.

La implementación no necesita Composer ni un contenedor de dependencias externo. Los servicios conservan sus constructores PHP con PDO para las pruebas y el uso existente. La fábrica centraliza la construcción de controladores en la entrada HTTP.

`AgendaValidator` y las dos consultas de disponibilidad expuestas por `HorarioMedicoService` conservan delegaciones compatibles. Las llamadas HTTP usan los módulos específicos. La matriz detallada de movimientos está en [COMPARACION-CADEFAR.md](../refactorizacion/COMPARACION-CADEFAR.md).

Los scripts, migraciones y seeders son infraestructura de instalación. La lógica del sistema y sus consultas de negocio siguen las capas anteriores.
