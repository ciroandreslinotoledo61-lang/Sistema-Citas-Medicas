# Referencia CADEFAR y decisiones

Se adopta separacion presentation/business/data/shared, nombres de accion y entidad, rutas por modulo y SQL educativo organizado. La clinica conserva PHP/PDO/PostgreSQL y frontend PHP/JS/CSS; no copia Node/Sequelize/React ni tablas del inventario.

Controllers leen HTTP, Services coordinan reglas, Validators validan entrada y Repositories ejecutan SQL preparado. Transaccion centraliza commit/rollback; no mueve decisiones de negocio a infraestructura. El [catálogo histórico](../arquitectura/catalogo-metodos.md) y la [defensa actualizada](../arquitectura/GUIA-ARQUITECTURA-Y-DEFENSA.md) explican las responsabilidades.

La referencia alternativa de otro proyecto no reemplaza el repositorio ni agrega entidades ajenas. Esta entrega parte del ZIP main(2), no de una version remota no verificada.
