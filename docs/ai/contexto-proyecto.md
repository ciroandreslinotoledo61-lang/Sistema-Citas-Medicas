# Contexto del proyecto

## Objetivo

Construir un sistema academico de citas para el Centro Medico Vida Salud usando PHP, HTML, CSS, JavaScript y PostgreSQL. La referencia de organizacion es CADEFAR Inventory, pero no se copia su stack Node/React.

## Entrega actual

Version 1: autenticacion por roles, gestion de cuentas administrativas y registro/consulta/contacto de pacientes. El registro crea una historia clinica vacia. Las 11 tablas del dominio estan definidas para sostener los siguientes avances.

El acceso del medico existe, pero no registra consultas todavia. No anunciar reservas, agenda, reportes, cambio de clave ni recuperacion por correo como funciones terminadas.

## Condiciones de trabajo

- Mantener backend, frontend y docs/ai separados.
- Escribir textos, rutas, comentarios y nombres sin acentos.
- Usar nombres completos, de verbo mas entidad, que permitan explicar el codigo.
- Mantener los archivos en UTF-8 sin BOM.
- No agregar frameworks o dependencias sin una necesidad concreta.
- SQL preparado en repositories; reglas y transacciones en services.
- No poner credenciales en Git ni exponer backend/src como carpeta publica.
- No usar datos reales en seeds ni pruebas.
- Un avance terminado debe incluir su documentacion y una verificacion relacionada con su riesgo.

## Decisiones iniciales

- Cuatro roles fijos; sin editor de roles.
- PostgreSQL como motor de datos; PDO como acceso desde PHP.
- Session PHP con cookie HttpOnly; sin JWT ni localStorage para credenciales.
- Dos servidores locales para conservar la separacion frontend/backend.
- Fechas locales del centro en America/La_Paz; eventos de registro en TIMESTAMPTZ.
- La estructura de tablas es un diseno inicial. Debe revisarse junto al diagrama academico antes de ampliar los modulos clinicos.
