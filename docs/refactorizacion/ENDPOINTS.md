# Endpoints vigentes

El contrato verificable de las **74 rutas** está en [`backend/tests/contratos/rutas.json`](../../backend/tests/contratos/rutas.json), contrastado con las definiciones de `backend/src/presentation/routes/`. Conserva las 60 rutas anteriores y añade 14 para recetas/PDF, sala de espera y pagos simulados.

Las rutas nuevas y sus permisos se describen en [la guía de recetas, sala y pagos](../uso/recetas-sala-pagos.md#api-y-arquitectura). Todas exigen sesión; las escrituras conservan CSRF global y validación por servicio. No se recuperó el antiguo endpoint de confirmación de citas ni se habilitó una API de pagos reales.
