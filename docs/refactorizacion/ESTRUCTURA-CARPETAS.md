# Árbol real de carpetas de la entrega

Este árbol enumera todos los directorios internos del proyecto, no una arquitectura simplificada. Los archivos de configuración y migraciones se destacan; cada carpeta conserva sus demás archivos fuente, pruebas o documentos. Se excluyen deliberadamente `.git/`, los `.env` privados y artefactos temporales generados por pruebas. No se eliminó ninguna carpeta funcional.

```text
citas-medicas/
├── backend/
│   ├── public/
│   ├── scripts/
│   ├── src/
│   │   ├── business/
│   │   │   ├── services/
│   │   │   └── validators/
│   │   ├── data/
│   │   │   ├── config/
│   │   │   ├── migrations/
│   │   │   │   ├── 001_crear_tablas_sistema.sql
│   │   │   │   ├── 002_unicidad_especialidades_medicos.sql
│   │   │   │   ├── 003_responsabilidades_citas.sql
│   │   │   │   ├── 004_agenda_30_minutos_y_perfiles.sql
│   │   │   │   ├── 005_programacion_y_cita_activa.sql
│   │   │   │   ├── 006_estado_programada.sql
│   │   │   │   ├── 007_bitacora_sistema.sql
│   │   │   │   ├── 008_consultas_historial_clinico.sql
│   │   │   │   ├── 009_seguridad_credenciales.sql
│   │   │   │   ├── 010_recuperacion_contrasena.sql
│   │   │   │   ├── 011_recetas_medicas.sql
│   │   │   │   ├── 012_sala_espera.sql
│   │   │   │   └── 013_pagos_demostracion.sql
│   │   │   ├── models/
│   │   │   ├── repositories/
│   │   │   └── seeders/
│   │   ├── presentation/
│   │   │   ├── controllers/
│   │   │   ├── http/
│   │   │   ├── middlewares/
│   │   │   └── routes/
│   │   └── shared/
│   │       ├── config/
│   │       ├── constants/
│   │       ├── errors/
│   │       └── utils/
│   ├── tests/
│   │   ├── contratos/
│   │   └── http/
│   └── .env.example
├── frontend/
│   ├── public/
│   ├── src/
│   │   ├── api/
│   │   ├── app/
│   │   ├── components/
│   │   ├── features/
│   │   │   ├── auth/
│   │   │   │   ├── pages/
│   │   │   │   └── services/
│   │   │   ├── bitacora/
│   │   │   │   ├── pages/
│   │   │   │   └── services/
│   │   │   ├── citas/
│   │   │   │   ├── pages/
│   │   │   │   ├── services/
│   │   │   │   └── utils/
│   │   │   ├── consultas/
│   │   │   │   ├── pages/
│   │   │   │   └── services/
│   │   │   ├── especialidades/
│   │   │   │   ├── pages/
│   │   │   │   └── services/
│   │   │   ├── horarios/
│   │   │   │   ├── pages/
│   │   │   │   └── services/
│   │   │   ├── inicio/
│   │   │   ├── medicos/
│   │   │   │   ├── pages/
│   │   │   │   └── services/
│   │   │   ├── pacientes/
│   │   │   │   ├── components/
│   │   │   │   ├── pages/
│   │   │   │   └── services/
│   │   │   ├── pagos/
│   │   │   │   ├── pages/
│   │   │   │   └── services/
│   │   │   ├── panel/
│   │   │   ├── recetas/
│   │   │   │   ├── pages/
│   │   │   │   └── services/
│   │   │   ├── reportes/
│   │   │   │   ├── pages/
│   │   │   │   └── services/
│   │   │   ├── sala-espera/
│   │   │   │   ├── pages/
│   │   │   │   └── services/
│   │   │   └── usuarios/
│   │   │       ├── pages/
│   │   │       └── services/
│   │   ├── layouts/
│   │   ├── routes/
│   │   └── styles/
│   └── .env.example
├── database/
│   └── sql-estudio/
├── deploy/
│   ├── public/
│   └── tests/
├── docs/
│   ├── arquitectura/
│   ├── historico/
│   ├── refactorizacion/
│   ├── rup/
│   ├── uso/
│   ├── entrega-final.md
│   └── README.md
├── .dockerignore
├── .editorconfig
├── .gitignore
├── AGENTS.md
├── Dockerfile
└── README.md
```

No todos los módulos necesitan `components/` o `utils/`; se muestran únicamente los que existen. No se agregaron carpetas vacías para hacer coincidir un dibujo. [Índice documental](../README.md), [arquitectura](../arquitectura/GUIA-ARQUITECTURA-Y-DEFENSA.md) e [informe de conservación y limpieza](../entrega-final.md).
