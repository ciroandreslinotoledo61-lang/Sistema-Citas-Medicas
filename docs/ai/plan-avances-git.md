# Plan de avances en Git

## Avances disponibles

| Avance | Contenido | Mensaje sugerido |
| --- | --- | --- |
| 1 | Acceso por roles, cuentas administrativas y pacientes | feat: agregar v1 con acceso por roles y registro de pacientes |
| 2 | Gestion de especialidades y medicos | feat: agregar gestion de especialidades y medicos |

El avance 2 combina los dos pasos solicitados. Incluye codigo, migracion, pruebas, pantallas y documentacion. El ZIP es una actualizacion de la V1, con parche y archivos nuevos/modificados.

No contiene un historial Git inventado ni un remoto configurado. Conserva tu repositorio y registra el avance despues de probarlo.

## Como registrar el avance 2

Consulta primero actualizacion-especialidades-medicos.md. Luego, desde tu repositorio:

```powershell
git status
git diff --stat
git add backend frontend docs README.md
git diff --cached --stat
git commit -m "feat: agregar gestion de especialidades y medicos"
git push
```

## Siguientes avances

| Orden | Resultado | Commit sugerido |
| --- | --- | --- |
| 3 | Horarios, validacion de cruces y turnos de 15 minutos | feat: generar turnos desde horarios medicos |
| 4 | Reserva con transaccion y control de concurrencia | feat: agregar reserva de citas |
| 5 | Cancelacion y liberacion del turno | feat: agregar cancelacion de citas |
| 6 | Consulta, tratamientos e historial autorizado | feat: agregar atencion medica e historial |
| 7 | Reportes y preparacion del despliegue | feat: agregar reportes y configuracion de despliegue |

Los avances 3 a 7 no estan implementados.

## Rutina por cambio

1. Crea una rama si quieres trabajar de forma separada, por ejemplo feat/horarios-medicos.
2. Revisa el requisito y la regla de negocio.
3. Implementa el cambio respetando las capas y los nombres completos.
4. Prueba la conducta nueva y sus errores relevantes.
5. Actualiza README, API, casos de uso o reglas cuando cambie el comportamiento.
6. Revisa git diff y agrega los archivos relacionados.
7. Crea un commit que describa el resultado y sube la rama.

Nunca agregues los .env reales, credenciales, dependencias locales ni bases con datos de personas.
