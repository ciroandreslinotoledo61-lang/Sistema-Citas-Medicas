# Plan de avances en Git

## Primer avance: esta entrega

Esta carpeta contiene una V1 completa para revisar y registrar en un commit. No contiene un historial Git inventado ni un remoto configurado. El README explica como crear tu propio repositorio.

Commit sugerido:

```text
feat: agregar v1 con acceso por roles y registro de pacientes
```

Incluye codigo, migracion, seeders, pruebas y documentacion. Excluye los .env reales, ZIP, dependencias locales y datos de usuarios.

## Orden para continuar

| Avance | Resultado | Commit sugerido |
| --- | --- | --- |
| 1 | Acceso, usuarios y pacientes | feat: agregar acceso por roles y registro de pacientes |
| 2 | Especialidades y cuentas de medicos | feat: agregar gestion de especialidades y medicos |
| 3 | Horarios y turnos de 15 minutos | feat: generar turnos desde horarios medicos |
| 4 | Reserva, cancelacion y concurrencia | feat: reservar y cancelar citas con transacciones |
| 5 | Consulta e historial autorizado | feat: registrar consultas y tratamientos |
| 6 | Reportes y preparacion de despliegue | feat: agregar reportes y configuracion de despliegue |

Estos son avances previstos, no funciones implementadas en la V1.

## Rutina por cambio

1. Crea una rama con una tarea concreta, por ejemplo `feat/horarios-medicos`.
2. Revisa el requisito y la regla de negocio.
3. Implementa el cambio respetando las capas.
4. Prueba la conducta nueva y sus errores relevantes.
5. Actualiza README, API o reglas si cambio el comportamiento.
6. Revisa `git diff`, agrega solo archivos relacionados y crea el commit.
7. Sube la rama cuando hayas configurado tu repositorio remoto.

```powershell
git switch -c feat/especialidades-medicos
git status
git diff
git add RUTAS_DE_LOS_ARCHIVOS_CAMBIADOS
git diff --cached
git commit -m "feat: agregar gestion de especialidades y medicos"
git push -u origin feat/especialidades-medicos
```

Reemplaza RUTAS_DE_LOS_ARCHIVOS_CAMBIADOS por rutas reales. No agregues ese texto literalmente.

Si ya tienes cambios locales, revisalos antes de cambiar de rama. No reinicialices ni reemplaces el historial de tu proyecto anterior.
