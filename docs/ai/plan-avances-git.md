# Plan de avances en Git

## Estado disponible

1. Autenticacion, usuarios y pacientes: implementado.
2. Especialidades y medicos: implementado.
3. Interfaz y navegacion Clinica: incluida en la base recibida.
4. Horarios, citas y responsabilidades por rol: version 1.2.0.
5. Agenda de 30 minutos, perfiles y reportes: version 1.3.0.
6. Programacion mensual, cita activa y resultados medicos: version 1.4.0.

## Commits de esta version

Desde el repositorio existente, luego de revisar y probar:

```powershell
git status
git diff --stat
git add backend
git commit -m "feat: programar periodos y proteger la cita activa del paciente"
git add frontend
git commit -m "feat: agregar programacion mensual y acciones de agenda medica"
git add README.md ACTUALIZAR-ESTA-VERSION.md docs
git commit -m "docs: documentar migracion 005 y pruebas de la version 1.4"
```

No incluir .env, copias de la base ni herramientas de revision. El paquete no cambia el remoto, no incluye una carpeta .git y no se subio a GitHub automaticamente.

## Proximos avances

| Orden | Resultado | Casos del README |
| --- | --- | --- |
| 1 | Validar el despliegue y concurrencia en PostgreSQL/XAMPP de la clinica | CU-20, CU-23, CU-24, CU-27 |
| 2 | Editar disponibilidad con manejo de citas afectadas | CU-31 |
| 3 | Consulta clinica e historial con permisos propios | CU-32, CU-33, CU-34 |
| 4 | Tratamientos | CU-35 |
| 5 | Cambio/recuperacion de clave y mantenimiento de roles | CU-37 a CU-39 |

Bitacora, consultorios y laboratorio requieren delimitar alcance antes de programarlos. En cada avance: revisar reglas, implementar por capas, probar comportamientos y errores, actualizar documentacion y crear un commit descriptivo.
