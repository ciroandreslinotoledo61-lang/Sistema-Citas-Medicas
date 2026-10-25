"""Contrato HTTP completo sobre una base de pruebas con las cuatro cuentas del seeder."""
import json, os, urllib.request, urllib.error, http.cookiejar, datetime, re

class Cliente:

    def __init__(self, base, nombre, registro):
        self.base = base
        self.nombre = nombre
        self.registro = registro
        self.cookies = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.cookies))

    def request(self, metodo, ruta, datos=None, esperado=200, csrf=True, headers=None, raw=None, registrar=True):
        cabeceras = {'Accept': 'application/json'}
        if metodo not in ['GET', 'OPTIONS', 'HEAD']:
            if csrf:
                token = self.request('GET', '/autenticacion/token', registrar=False)['datos']['tokenCsrf']
                cabeceras['X-CSRF-Token'] = token
            cabeceras['Content-Type'] = 'application/json'
        cabeceras.update(headers or {})
        body = raw if raw is not None else json.dumps(datos or {}).encode() if metodo not in ['GET', 'OPTIONS', 'HEAD'] else None
        req = urllib.request.Request(self.base + ruta, data=body, headers=cabeceras, method=metodo)
        try:
            with self.opener.open(req, timeout=15) as res:
                codigo = res.status
                contenido = res.read()
        except urllib.error.HTTPError as e:
            codigo = e.code
            contenido = e.read()
        try:
            respuesta = json.loads(contenido) if contenido else {}
        except ValueError:
            raise AssertionError(f'{metodo} {ruta}: no es JSON: {contenido[:300]!r}')
        assert codigo == esperado, (self.nombre, metodo, ruta, codigo, esperado, respuesta)
        if registrar:
            self.registro.append({'actor': self.nombre, 'metodo': metodo, 'ruta': ruta, 'codigo': codigo, 'respuesta': respuesta})
        return respuesta

    def login(self, correo, clave):
        return self.request('POST', '/autenticacion/iniciar-sesion', {'correoUsuario': correo, 'contrasenaUsuario': clave})

def ejecutar(base, front, clave):
    log = []
    anon = Cliente(base, 'anonimo', log)
    anon.request('GET', '/estado')
    anon.request('GET', '/autenticacion/token')
    anon.request('GET', '/usuarios', esperado=401)
    anon.request('POST', '/autenticacion/iniciar-sesion', {}, esperado=419, csrf=False)
    anon.request('POST', '/autenticacion/iniciar-sesion', raw=b'{', esperado=400)
    anon.request('POST', '/autenticacion/iniciar-sesion', raw=b'[]', esperado=400)
    anon.request('POST', '/autenticacion/iniciar-sesion', raw=b'x', headers={'Content-Type': 'text/plain'}, esperado=415)
    anon.request('GET', '/usuarios', headers={'Origin': 'https://otro.example'}, esperado=403)
    anon.request('GET', '/no-existe', esperado=404)
    anon.request('OPTIONS', '/citas', esperado=204)
    clientes = {rol: Cliente(base, rol, log) for rol in ['administrador', 'recepcionista', 'medico', 'paciente']}
    for rol, cliente in clientes.items():
        usuario = cliente.login(rol + '@vidasalud.test', clave)['datos']
        assert usuario['codigoRol'] == rol
        cliente.request('GET', '/autenticacion/sesion')
    admin = clientes['administrador']
    recep = clientes['recepcionista']
    paciente = clientes['paciente']
    medico = clientes['medico']
    matrices = {'/usuarios': {'administrador'}, '/roles': {'administrador'}, '/pacientes': {'administrador', 'recepcionista'}, '/especialidades': {'administrador'}, '/medicos': {'administrador'}, '/reportes': {'administrador'}, '/horarios': {'administrador', 'medico'}, '/agenda/medicos': {'administrador', 'recepcionista', 'medico'}, '/disponibilidad/medicos': {'administrador', 'recepcionista', 'paciente'}, '/disponibilidad/configuracion': set(clientes), '/pacientes/mi-perfil': {'paciente'}}
    for ruta, permitidos in matrices.items():
        for rol, cliente in clientes.items():
            cliente.request('GET', ruta, esperado=200 if rol in permitidos else 403)
    admin.request('GET', '/usuarios/99999999999999999999999999999999999', esperado=422)
    admin.request('DELETE', '/usuarios', esperado=404)
    datos = {'nombresUsuario': 'Contrato', 'apellidosUsuario': 'HTTP', 'correoUsuario': 'contrato-recepcion@example.test', 'contrasenaUsuario': clave, 'confirmacionContrasena': clave, 'codigoRol': 'recepcionista'}
    u = admin.request('POST', '/usuarios', datos, 201)['datos']
    uid = u['idUsuario']
    admin.request('GET', f'/usuarios/{uid}')
    admin.request('PATCH', f'/usuarios/{uid}', datos | {'nombresUsuario': 'Contrato editado'})
    admin.request('PATCH', f'/usuarios/{uid}/estado', {'usuarioActivo': False})
    especialidad = admin.request('POST', '/especialidades', {'nombreEspecialidad': 'Especialidad Contrato HTTP'}, 201)['datos']
    eid = especialidad['idEspecialidad']
    admin.request('GET', f'/especialidades/{eid}')
    admin.request('GET', '/especialidades/activas')
    admin.request('PATCH', f'/especialidades/{eid}', {'nombreEspecialidad': 'Especialidad Contrato HTTP', 'descripcionEspecialidad': 'Prueba de arquitectura'})
    admin.request('PATCH', f'/especialidades/{eid}/estado', {'especialidadActiva': False})
    admin.request('PATCH', f'/especialidades/{eid}/estado', {'especialidadActiva': True})
    dm = datos | {'correoUsuario': 'contrato-medico@example.test', 'matriculaProfesional': 'CONTRATO-HTTP', 'idEspecialidad': eid, 'telefonoMedico': '70000002'}
    m = admin.request('POST', '/medicos', dm, 201)['datos']
    mid = m['idMedico']
    admin.request('GET', f'/medicos/{mid}')
    admin.request('PATCH', f'/medicos/{mid}', dm)
    admin.request('PATCH', f'/medicos/{mid}/estado', {'medicoActivo': False})
    admin.request('PATCH', f'/medicos/{mid}/estado', {'medicoActivo': True})
    medicoNuevo = Cliente(base, 'medico_asignado', log)
    medicoNuevo.login(dm['correoUsuario'], clave)
    dp = datos | {'correoUsuario': 'contrato-paciente@example.test', 'cedulaIdentidad': 'HTTP-CONTRATO', 'fechaNacimiento': '2000-01-01', 'telefonoPaciente': '70000003', 'direccionPaciente': 'Santa Cruz', 'nombreContactoEmergencia': '', 'telefonoContactoEmergencia': ''}
    p = anon.request('POST', '/autenticacion/registro-paciente', dp, 201)['datos']
    pid = p['idPaciente']
    assert p['idHistoriaClinica'] > 0
    nuevo = Cliente(base, 'paciente_nuevo', log)
    cuenta = nuevo.login(dp['correoUsuario'], clave)
    assert cuenta['datos']['codigoRol'] == 'paciente'
    segundo = recep.request('POST', '/pacientes', dp | {'correoUsuario': 'contrato-segundo@example.test', 'cedulaIdentidad': 'HTTP-SEGUNDO'}, 201)['datos']
    recep.request('GET', f'/pacientes/{pid}')
    recep.request('PATCH', f'/pacientes/{pid}', dp)
    recep.request('PATCH', f'/pacientes/{pid}/contacto', {k: dp[k] for k in ['telefonoPaciente', 'direccionPaciente', 'nombreContactoEmergencia', 'telefonoContactoEmergencia']})
    recep.request('PATCH', f"/pacientes/{segundo['idPaciente']}/estado", {'pacienteActivo': False})
    recep.request('PATCH', f"/pacientes/{segundo['idPaciente']}/estado", {'pacienteActivo': True})
    anon.request('POST', '/autenticacion/registro-paciente', dp | {'correoUsuario': 'contrato-duplicado@example.test'}, esperado=409)
    assert admin.request('GET', '/usuarios?busqueda=contrato-duplicado')['datos']['elementos'] == []
    dia = (datetime.datetime.now(datetime.timezone(datetime.timedelta(hours=-4))).date() + datetime.timedelta(days=7)).isoformat()
    horario = {'idMedico': mid, 'fechaAtencion': dia, 'horaInicio': '08:00', 'horaFin': '10:00'}
    creado = admin.request('POST', '/horarios', horario, 201)['datos']
    assert creado['cantidadTurnos'] == 4 and creado['duracionCitaMinutos'] == 30
    periodo = {'idMedico': mid, 'fechaInicial': dia, 'fechaFinal': dia, 'diasSemana': list(range(1, 8)), 'horaInicio': '10:00', 'horaFin': '11:00'}
    admin.request('POST', '/horarios/programacion', periodo)
    admin.request('POST', '/horarios/programacion', periodo)
    turnos = nuevo.request('GET', f'/disponibilidad/turnos?idMedico={mid}&fecha={dia}')['datos']
    assert len(turnos) == 6
    primera = nuevo.request('POST', '/citas', {'idTurno': turnos[0]['idTurno'], 'motivoCita': 'Control HTTP'}, 201)['datos']
    cid = primera['idCita']
    nuevo.request('GET', '/citas')
    nuevo.request('GET', '/citas/activas')
    nuevo.request('GET', f'/citas/{cid}')
    nuevo.request('POST', '/citas', {'idTurno': turnos[1]['idTurno']}, esperado=409)
    paciente.request('POST', '/citas', {'idTurno': turnos[0]['idTurno']}, esperado=409)
    paciente.request('GET', f'/citas/{cid}', esperado=404)
    medico.request('GET', f'/citas/{cid}', esperado=404)
    medicoNuevo.request('GET', f'/citas/{cid}')
    paciente.request('PATCH', f'/citas/{cid}/cancelacion', {}, esperado=404)
    nuevo.request('PATCH', f'/citas/{cid}/reprogramacion', {'idTurno': turnos[1]['idTurno']}, esperado=403)
    recep.request('PATCH', f'/citas/{cid}/asistencia', {'asistenciaRegistrada': 'asistio'}, esperado=409)
    medicoNuevo.request('PATCH', f'/citas/{cid}/atencion', {}, esperado=409)
    medicoNuevo.request('PATCH', f'/citas/{cid}/inasistencia', {}, esperado=409)
    recep.request('PATCH', f'/citas/{cid}/confirmacion', {}, esperado=404)
    segunda = recep.request('PATCH', f'/citas/{cid}/reprogramacion', {'idTurno': turnos[1]['idTurno']}, 201)['datos']
    cid2 = segunda['idCita']
    assert segunda['idCitaOrigen'] == cid
    assert nuevo.request('GET', f'/citas/{cid}')['datos']['estadoCita'] == 'reprogramada'
    medicoNuevo.request('PATCH', f'/citas/{cid2}/cancelacion', {}, esperado=422)
    medicoNuevo.request('PATCH', f'/citas/{cid2}/cancelacion', {'motivoCancelacion': 'Prueba HTTP completada'})
    assert len(nuevo.request('GET', f'/disponibilidad/turnos?idMedico={mid}&fecha={dia}')['datos']) == 6
    admin.request('GET', f'/reportes?fechaDesde={dia}&fechaHasta={dia}')
    vistas = ['/panel', '/usuarios', '/usuarios/nuevo', f'/usuarios/{uid}/editar', '/pacientes', '/pacientes/nuevo', f'/pacientes/{pid}', '/especialidades', '/especialidades/nueva', f'/especialidades/{eid}/editar', '/medicos', '/medicos/nuevo', f'/medicos/{mid}/editar', '/citas', '/citas/nueva', f'/citas/{cid}', f'/citas/{cid2}/reprogramar', '/horarios', '/horarios/nuevo', '/reportes']
    for vista in vistas:
        with admin.opener.open(front + vista, timeout=15) as res:
            html = res.read().decode()
            assert res.status == 200 and 'data-api-url' in html, (vista, res.status)
            assert 'Fatal error' not in html and 'Warning:' not in html, vista
            log.append({'actor': 'administrador', 'vista': vista, 'codigo': res.status})
    with nuevo.opener.open(front + '/mi-perfil') as res:
        assert res.status == 200
    try:
        paciente.opener.open(front + '/usuarios')
        raise AssertionError('Vista administrativa abierta al paciente')
    except urllib.error.HTTPError as e:
        assert e.code == 403
    nuevo.request('POST', '/autenticacion/cerrar-sesion')
    nuevo.request('GET', '/autenticacion/sesion', esperado=401)
    return log

def normalizar(valor, clave=''):
    if isinstance(valor, dict):
        return {k: normalizar(v, k) for k, v in valor.items()}
    if isinstance(valor, list):
        return [normalizar(v, clave) for v in valor]
    if clave in ['tokenCsrf', 'fechaReserva', 'fechaActualizacion', 'fechaRegistroCita', 'fechaActualizacionCita', 'fecha']:
        return '<dinamico>'
    if isinstance(valor, str) and re.match('^\\d{4}-\\d\\d-\\d\\d[T ]\\d\\d:', valor) and (clave not in ['fechaHoraInicio', 'fechaHoraFin']):
        return '<momento>'
    return valor
