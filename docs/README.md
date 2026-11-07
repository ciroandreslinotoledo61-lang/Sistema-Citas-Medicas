# Documentación de Clínica Vida Salud

Punto de entrada general: [README del proyecto](../README.md). El código y las migraciones son la fuente de verdad de la implementación; los antecedentes históricos no sustituyen los procedimientos vigentes.

## Para la revisión académica

- [Guía de arquitectura y defensa](arquitectura/GUIA-ARQUITECTURA-Y-DEFENSA.md).
- [Requisitos](rup/requisitos.md), [casos de uso](rup/casos-uso.md), [análisis y diseño](rup/analisis-diseno.md), [trazabilidad](rup/trazabilidad.md) e [informe RUP](rup/informe-proyecto.md).
- [Árbol real de carpetas](refactorizacion/ESTRUCTURA-CARPETAS.md) y [contratos API](refactorizacion/ENDPOINTS.md).
- [Informe de entrega: limpieza, conservación y pruebas](entrega-final.md).

## Para instalar, probar y operar

- [Instalación local](uso/instalacion-windows.md) y [manual de roles](uso/manual-roles.md).
- [Pruebas PHP/HTTP/JavaScript](uso/pruebas-php.md).
- [Autenticación y credenciales](uso/usuarios-autenticacion.md); [privacidad y recuperación](uso/actualizacion-privacidad-recuperacion.md).
- [Agenda y seed de demostración](uso/correcciones-agenda-demostracion.md).
- [Recetas, sala de espera y pagos de prueba](uso/recetas-sala-pagos.md).
- [Cancelación e implementación del cron de vencidas](uso/citas-vencidas-pagos.md).
- [Respaldos y restauración aislada](uso/respaldo-recuperacion.md) y [publicación segura](uso/despliegue.md).
- [SQL de estudio](../database/sql-estudio/README.md): solo bases didácticas independientes.

## Antecedentes conservados

`refactorizacion/` conserva decisiones e informes de cambios; `historico/` contiene un inventario de una versión anterior. El [catálogo de métodos 1.5](arquitectura/catalogo-metodos.md) se conserva como referencia histórica, no como listado exhaustivo de firmas actuales. La [propuesta de permisos clínicos](uso/propuesta-permisos-clinicos.md) no concede por sí misma nuevos accesos.

`AGENTS.md` permanece en la raíz para que las herramientas lo descubran automáticamente. Los respaldos, clústeres de pruebas, logs y capturas privados no forman parte de la documentación pública; no se deben adjuntar a GitHub.
