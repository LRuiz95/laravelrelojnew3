-- ======================================================================
-- ESQUEMA MYSQL COMPLETO — SOLO TABLAS USADAS EN rh-dashboard2.php
-- Extraido de Firebird: DATOS (1).FDB
-- Tablas: 26
-- ======================================================================

DROP DATABASE IF EXISTS GES;
CREATE DATABASE GES CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE GES;

-- ======================================================================
-- TABLA: CFGSEDES  |  8 columnas  |  ~5 registros
-- ======================================================================
CREATE TABLE cfgsedes (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  id_campus SMALLINT NOT NULL,
  clave_sede_dgp INT DEFAULT NULL,
  descripcion VARCHAR(50) DEFAULT NULL,
  domicilio VARCHAR(100) DEFAULT NULL,
  cp VARCHAR(10) DEFAULT NULL,
  ciudad VARCHAR(100) DEFAULT NULL,
  estado VARCHAR(5) DEFAULT NULL,
  PRIMARY KEY (id_escuela, id_campus)
) ENGINE=InnoDB;

-- ======================================================================
-- TABLA: CFGPLANES_DET  |  45 columnas  |  ~1859 registros
-- ======================================================================
CREATE TABLE cfgplanes_det (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  id_plan VARCHAR(10) NOT NULL,
  id_tipoeval VARCHAR(255) NOT NULL,
  id_etapa VARCHAR(10) NOT NULL,
  claveasignatura VARCHAR(15) NOT NULL,
  nombreasignatura VARCHAR(100) DEFAULT NULL,
  nombreasignatura_opta VARCHAR(100) DEFAULT NULL,
  nombrecorto VARCHAR(10) DEFAULT NULL,
  id_metodologia VARCHAR(15) DEFAULT NULL,
  oficial TEXT DEFAULT NULL,
  creditos DOUBLE DEFAULT NULL,
  seriacion VARCHAR(15) NOT NULL,
  seriacion2 VARCHAR(15) NOT NULL,
  seriacion3 VARCHAR(15) NOT NULL,
  seriacion4 VARCHAR(15) NOT NULL,
  seriacion5 VARCHAR(15) NOT NULL,
  horas_teoria SMALLINT DEFAULT NULL,
  horas_practica SMALLINT DEFAULT NULL,
  grado SMALLINT DEFAULT NULL,
  area VARCHAR(50) DEFAULT NULL,
  opcional TEXT DEFAULT NULL,
  promediable TEXT DEFAULT NULL,
  custom_eval TEXT DEFAULT NULL,
  descripcion TEXT DEFAULT NULL,
  formula VARCHAR(250) DEFAULT NULL,
  auxiliar VARCHAR(10) DEFAULT NULL,
  ignorar_redondeo_en_calculadas TEXT DEFAULT NULL,
  simultanea VARCHAR(15) NOT NULL,
  simultanea2 VARCHAR(15) NOT NULL,
  simultanea3 VARCHAR(15) NOT NULL,
  simultanea4 VARCHAR(15) NOT NULL,
  simultanea5 VARCHAR(15) NOT NULL,
  default_params TEXT DEFAULT NULL,
  minimo DOUBLE DEFAULT NULL,
  maximo DOUBLE DEFAULT NULL,
  minimo_aprobatorio DOUBLE DEFAULT NULL,
  utilizar_fechas TEXT DEFAULT NULL,
  utilizar_obs TEXT DEFAULT NULL,
  creditos_requeridos DOUBLE DEFAULT NULL,
  status_asignatura TEXT DEFAULT NULL,
  ver_en_aulaescolar TEXT DEFAULT NULL,
  clave_idasignatura INT DEFAULT NULL,
  id_tipoasignatura INT DEFAULT NULL,
  fecha_ult_sync_aula DATETIME DEFAULT NULL,
  fecha_ult_sync_evals_aula DATETIME DEFAULT NULL,
  PRIMARY KEY (id_escuela, id_plan, id_tipoeval, id_etapa, claveasignatura)
) ENGINE=InnoDB;

CREATE INDEX idx_cfgplanes_det_cfgplanes_detindex1 ON cfgplanes_det (id_escuela, id_plan, id_tipoeval, id_etapa, grado, claveasignatura);
CREATE INDEX idx_cfgplanes_det_cfgplanes_detindex2 ON cfgplanes_det (id_escuela, id_plan, claveasignatura);

-- ======================================================================
-- TABLA: PROFESORES  |  65 columnas  |  ~197 registros
-- ======================================================================
CREATE TABLE profesores (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  claveprofesor VARCHAR(10) NOT NULL,
  numempleado VARCHAR(30) DEFAULT NULL,
  nombreprofesor VARCHAR(100) DEFAULT NULL,
  genero VARCHAR(1) DEFAULT NULL,
  cedula_fiscal VARCHAR(20) DEFAULT NULL,
  clave_ciudadana VARCHAR(30) DEFAULT NULL,
  estado_civil TEXT DEFAULT NULL,
  fecha_nacimiento DATETIME DEFAULT NULL,
  lugar_nacimiento VARCHAR(100) DEFAULT NULL,
  estado_nacimiento VARCHAR(5) DEFAULT NULL,
  nacionalidad VARCHAR(30) DEFAULT NULL,
  documento_migratorio VARCHAR(50) DEFAULT NULL,
  domicilio VARCHAR(100) DEFAULT NULL,
  cp VARCHAR(10) DEFAULT NULL,
  ciudad VARCHAR(100) DEFAULT NULL,
  estado VARCHAR(5) DEFAULT NULL,
  latitud DOUBLE DEFAULT NULL,
  longitud DOUBLE DEFAULT NULL,
  telefono VARCHAR(30) DEFAULT NULL,
  celular VARCHAR(30) DEFAULT NULL,
  telefono_oficina VARCHAR(30) DEFAULT NULL,
  id_campus SMALLINT DEFAULT NULL,
  nivel VARCHAR(10) DEFAULT NULL,
  statusactual VARCHAR(2) DEFAULT NULL,
  fecha_ingreso DATETIME DEFAULT NULL,
  especialidad VARCHAR(50) DEFAULT NULL,
  anotaciones TEXT DEFAULT NULL,
  fecha_baja DATETIME DEFAULT NULL,
  departamento VARCHAR(50) DEFAULT NULL,
  cargo VARCHAR(50) DEFAULT NULL,
  nivel_estudios VARCHAR(50) DEFAULT NULL,
  titulado TEXT DEFAULT NULL,
  institucion_estudios VARCHAR(50) DEFAULT NULL,
  cedula_profesional VARCHAR(50) DEFAULT NULL,
  horasdocencia INT DEFAULT NULL,
  horasinvestigacion INT DEFAULT NULL,
  horasadministrativas INT DEFAULT NULL,
  sueldo DOUBLE DEFAULT NULL,
  fotografia LONGBLOB DEFAULT NULL,
  fecha_creacion DATETIME DEFAULT NULL,
  fecha_actualizacion DATETIME DEFAULT NULL,
  username VARCHAR(15) NOT NULL,
  username_actualiza VARCHAR(15) DEFAULT NULL,
  tarjeta_id VARCHAR(50) DEFAULT NULL,
  huella1 LONGBLOB DEFAULT NULL,
  huella2 LONGBLOB DEFAULT NULL,
  huella1_template LONGBLOB DEFAULT NULL,
  huella2_template LONGBLOB DEFAULT NULL,
  nip VARCHAR(50) DEFAULT NULL,
  numero_seguridadsocial VARCHAR(35) DEFAULT NULL,
  email VARCHAR(255) DEFAULT NULL,
  origen_horario VARCHAR(2) DEFAULT NULL,
  contrato VARCHAR(10) DEFAULT NULL,
  clave_categoria VARCHAR(10) DEFAULT NULL,
  forma_pago VARCHAR(10) DEFAULT NULL,
  banco VARCHAR(150) DEFAULT NULL,
  banco_cuenta VARCHAR(20) DEFAULT NULL,
  banco_clabe VARCHAR(20) DEFAULT NULL,
  banco_sucursal VARCHAR(50) DEFAULT NULL,
  pension_alimenticia TEXT DEFAULT NULL,
  pension_alimenticia_benef INT DEFAULT NULL,
  sueldo_x_hora DOUBLE DEFAULT NULL,
  fecha_ult_sync_aula DATETIME DEFAULT NULL,
  fecha_ult_sync_asigns_aula DATETIME DEFAULT NULL,
  PRIMARY KEY (id_escuela, claveprofesor)
) ENGINE=InnoDB;

CREATE INDEX idx_profesores_idx1_profesores ON profesores (id_escuela, numempleado);

-- ======================================================================
-- TABLA: CFGSESIONES  |  8 columnas  |  ~1062 registros
-- ======================================================================
CREATE TABLE cfgsesiones (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  nivel VARCHAR(10) NOT NULL,
  sesion SMALLINT NOT NULL,
  descripcion VARCHAR(50) NOT NULL,
  hora_inicio DATETIME NOT NULL,
  hora_fin DATETIME NOT NULL,
  receso TEXT DEFAULT NULL,
  turno VARCHAR(2) NOT NULL,
  PRIMARY KEY (id_escuela, nivel, sesion)
) ENGINE=InnoDB;

CREATE INDEX idx_cfgsesiones_cfgsesionesindex1 ON cfgsesiones (id_escuela, nivel, turno, sesion);

-- ======================================================================
-- TABLA: GRUPOS  |  29 columnas  |  ~1057 registros
-- ======================================================================
CREATE TABLE grupos (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  inicial SMALLINT NOT NULL,
  final SMALLINT NOT NULL,
  periodo SMALLINT NOT NULL,
  codigo_grupo VARCHAR(15) NOT NULL,
  tipo_grupo VARCHAR(2) NOT NULL,
  cupo_maximo SMALLINT DEFAULT NULL,
  inscritos SMALLINT DEFAULT NULL,
  nivel VARCHAR(10) NOT NULL,
  grado SMALLINT NOT NULL,
  grupo VARCHAR(15) NOT NULL,
  turno VARCHAR(2) DEFAULT NULL,
  ciclo_cerrado TEXT DEFAULT NULL,
  ciclo_cerrado_cxc TEXT DEFAULT NULL,
  permitir_diferentes_niveles TEXT DEFAULT NULL,
  controlar_inscripciones TEXT DEFAULT NULL,
  claveprofesor_titular VARCHAR(10) DEFAULT NULL,
  titular_todasasignaturas TEXT DEFAULT NULL,
  claveprofesor_suplente VARCHAR(10) DEFAULT NULL,
  id_campus SMALLINT DEFAULT NULL,
  asistencia_lun TEXT DEFAULT NULL,
  asistencia_mar TEXT DEFAULT NULL,
  asistencia_mie TEXT DEFAULT NULL,
  asistencia_jue TEXT DEFAULT NULL,
  asistencia_vie TEXT DEFAULT NULL,
  asistencia_sab TEXT DEFAULT NULL,
  asistencia_dom TEXT DEFAULT NULL,
  fecha_ult_sync_aula DATETIME DEFAULT NULL,
  fecha_ult_sync_als_aula DATETIME DEFAULT NULL,
  PRIMARY KEY (id_escuela, inicial, final, periodo, codigo_grupo)
) ENGINE=InnoDB;

-- ======================================================================
-- TABLA: CFGTURNOS  |  3 columnas  |  ~10 registros
-- ======================================================================
CREATE TABLE cfgturnos (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  turno VARCHAR(2) NOT NULL,
  descripcionturno VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (id_escuela, turno)
) ENGINE=InnoDB;

-- ======================================================================
-- TABLA: CFGNIVELES  |  34 columnas  |  ~25 registros
-- ======================================================================
CREATE TABLE cfgniveles (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  nivel VARCHAR(10) NOT NULL,
  tipo_nivel SMALLINT DEFAULT NULL,
  descripcion VARCHAR(50) NOT NULL,
  director VARCHAR(50) NOT NULL,
  director_clave_ciudadana VARCHAR(30) DEFAULT NULL,
  clave_idcargo INT DEFAULT NULL,
  director_firmadigital LONGBLOB DEFAULT NULL,
  cargo VARCHAR(50) DEFAULT NULL,
  jefe_servs VARCHAR(50) DEFAULT NULL,
  jefe_servs_clave_ciudadana VARCHAR(30) DEFAULT NULL,
  clave_idcargo_servs INT DEFAULT NULL,
  jefe_servs_firmadigital LONGBLOB DEFAULT NULL,
  cargo_servs VARCHAR(50) DEFAULT NULL,
  acuerdo VARCHAR(30) DEFAULT NULL,
  acuerdo_fecha DATETIME DEFAULT NULL,
  clave_idtipoperiodo INT DEFAULT NULL,
  auxstr1 VARCHAR(50) DEFAULT NULL,
  auxnumerico1 DOUBLE DEFAULT NULL,
  auxstr2 VARCHAR(50) DEFAULT NULL,
  auxnumerico2 DOUBLE DEFAULT NULL,
  zonaescolar VARCHAR(20) DEFAULT NULL,
  mostrar_todos_grados TEXT DEFAULT NULL,
  logo_nivel LONGBLOB DEFAULT NULL,
  id_seriematricula SMALLINT DEFAULT NULL,
  auxstr3 VARCHAR(50) DEFAULT NULL,
  pesotallavacunas TEXT DEFAULT NULL,
  titulacion TEXT DEFAULT NULL,
  autorizado_titulos_director TEXT DEFAULT NULL,
  autorizado_titulos_titular TEXT DEFAULT NULL,
  cedula_fiscal_director VARCHAR(20) DEFAULT NULL,
  cedula_fiscal_titular VARCHAR(20) DEFAULT NULL,
  nombre_escuela VARCHAR(100) DEFAULT NULL,
  procesar_metricas TEXT DEFAULT NULL,
  PRIMARY KEY (id_escuela, nivel)
) ENGINE=InnoDB;

-- ======================================================================
-- TABLA: CICLOS  |  14 columnas  |  ~30 registros
-- ======================================================================
CREATE TABLE ciclos (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  inicial SMALLINT NOT NULL,
  final SMALLINT NOT NULL,
  periodo SMALLINT NOT NULL,
  descripcion VARCHAR(50) DEFAULT NULL,
  denom_periodo VARCHAR(15) DEFAULT NULL,
  codigo_corto VARCHAR(20) DEFAULT NULL,
  fecha_inicial DATETIME DEFAULT NULL,
  fecha_final DATETIME DEFAULT NULL,
  incluir_procesos TEXT DEFAULT NULL,
  mostrar_barra TEXT DEFAULT NULL,
  mostrar_webges TEXT DEFAULT NULL,
  fecha_ult_sync_aula DATETIME DEFAULT NULL,
  mostrar_controlpagos TEXT DEFAULT NULL,
  PRIMARY KEY (id_escuela, inicial, final, periodo)
) ENGINE=InnoDB;

-- ======================================================================
-- TABLA: ALUMNOS_GRUPOS  |  22 columnas  |  ~21862 registros
-- ======================================================================
CREATE TABLE alumnos_grupos (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  inicial SMALLINT NOT NULL,
  final SMALLINT NOT NULL,
  periodo SMALLINT NOT NULL,
  numeroalumno INT NOT NULL,
  num SMALLINT NOT NULL,
  tipo VARCHAR(5) NOT NULL,
  codigo_grupo VARCHAR(15) NOT NULL,
  id_plan VARCHAR(10) DEFAULT NULL,
  id_etapa VARCHAR(10) DEFAULT NULL,
  id_tipoeval VARCHAR(5) DEFAULT NULL,
  claveasignatura VARCHAR(15) DEFAULT NULL,
  tipoexamen SMALLINT DEFAULT NULL,
  fecha DATETIME DEFAULT NULL,
  reinscrito TEXT DEFAULT NULL,
  relacion_cxc TEXT DEFAULT NULL,
  aux1 INT DEFAULT NULL,
  aux2 INT DEFAULT NULL,
  aux3 INT DEFAULT NULL,
  caux1 VARCHAR(15) DEFAULT NULL,
  caux2 VARCHAR(15) DEFAULT NULL,
  caux3 VARCHAR(15) DEFAULT NULL,
  PRIMARY KEY (id_escuela, inicial, final, periodo, numeroalumno, num)
) ENGINE=InnoDB;

CREATE INDEX idx_alumnos_grupos_alumnos_gruposindex1 ON alumnos_grupos (id_escuela, id_plan, id_etapa, id_tipoeval, claveasignatura);
CREATE INDEX idx_alumnos_grupos_al_gruposindex1 ON alumnos_grupos (id_escuela, inicial, final, periodo, tipo, codigo_grupo, numeroalumno);
CREATE INDEX idx_alumnos_grupos_al_gruposindex2 ON alumnos_grupos (id_escuela, inicial, final, periodo, tipo, codigo_grupo, id_plan, id_etapa, id_tipoeval, claveasignatura, tipoexamen, numeroalumno);
CREATE INDEX idx_alumnos_grupos_al_gruposindex3 ON alumnos_grupos (id_escuela, numeroalumno, tipo, id_plan, id_etapa, id_tipoeval, claveasignatura, tipoexamen);

-- ======================================================================
-- TABLA: ALUMNOS  |  115 columnas  |  ~6518 registros
-- ======================================================================
CREATE TABLE alumnos (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  numeroalumno INT NOT NULL,
  paterno VARCHAR(30) DEFAULT NULL,
  materno VARCHAR(30) DEFAULT NULL,
  nombre VARCHAR(30) DEFAULT NULL,
  genero VARCHAR(1) DEFAULT NULL,
  nivel VARCHAR(10) NOT NULL,
  grado SMALLINT DEFAULT NULL,
  subnivel VARCHAR(10) DEFAULT NULL,
  matricula VARCHAR(30) DEFAULT NULL,
  matricula_oficial VARCHAR(30) DEFAULT NULL,
  status VARCHAR(2) DEFAULT NULL,
  clave_ciudadana VARCHAR(30) DEFAULT NULL,
  estado_civil TEXT DEFAULT NULL,
  fecha_nacimiento DATETIME DEFAULT NULL,
  domicilio VARCHAR(100) DEFAULT NULL,
  entre_calles VARCHAR(50) DEFAULT NULL,
  cp VARCHAR(10) DEFAULT NULL,
  ciudad VARCHAR(100) DEFAULT NULL,
  estado VARCHAR(5) DEFAULT NULL,
  latitud DOUBLE DEFAULT NULL,
  longitud DOUBLE DEFAULT NULL,
  telefono VARCHAR(30) DEFAULT NULL,
  celular VARCHAR(30) DEFAULT NULL,
  telefonotrabajo VARCHAR(30) DEFAULT NULL,
  nombretutor VARCHAR(100) DEFAULT NULL,
  parentesco TEXT DEFAULT NULL,
  observaciones VARCHAR(255) DEFAULT NULL,
  adicionales TEXT DEFAULT NULL,
  fotografia LONGBLOB DEFAULT NULL,
  id_familia INT DEFAULT NULL,
  facturar_x_familia TEXT DEFAULT NULL,
  facturar_id_empresa INT DEFAULT NULL,
  facturar_a VARCHAR(100) DEFAULT NULL,
  facturar_cedula_fiscal VARCHAR(20) DEFAULT NULL,
  facturar_domicilio VARCHAR(100) DEFAULT NULL,
  facturar_numext VARCHAR(25) DEFAULT NULL,
  facturar_numint VARCHAR(25) DEFAULT NULL,
  facturar_colonia VARCHAR(50) DEFAULT NULL,
  facturar_cp VARCHAR(10) DEFAULT NULL,
  facturar_localidad VARCHAR(50) DEFAULT NULL,
  facturar_ciudad VARCHAR(100) DEFAULT NULL,
  facturar_estado VARCHAR(5) DEFAULT NULL,
  facturar_telefono VARCHAR(30) DEFAULT NULL,
  facturar_fax VARCHAR(30) DEFAULT NULL,
  facturar_email VARCHAR(255) DEFAULT NULL,
  facturar_uso_cfdi VARCHAR(20) DEFAULT NULL,
  puesto_empresa VARCHAR(50) DEFAULT NULL,
  email VARCHAR(255) DEFAULT NULL,
  email_alterno VARCHAR(255) DEFAULT NULL,
  fecha_baja DATETIME DEFAULT NULL,
  motivo_baja VARCHAR(100) DEFAULT NULL,
  anioegreso SMALLINT DEFAULT NULL,
  lugar_nacimiento VARCHAR(100) DEFAULT NULL,
  estado_nacimiento VARCHAR(5) DEFAULT NULL,
  nacionalidad VARCHAR(30) DEFAULT NULL,
  escuela_procedencia VARCHAR(100) DEFAULT NULL,
  escolaridad VARCHAR(50) DEFAULT NULL,
  estado_escolaridad VARCHAR(5) DEFAULT NULL,
  fecha_egreso DATETIME DEFAULT NULL,
  fecha_ingreso DATETIME DEFAULT NULL,
  tipo_financiamiento VARCHAR(5) DEFAULT NULL,
  fecha_creacion DATETIME DEFAULT NULL,
  fecha_actualizacion DATETIME DEFAULT NULL,
  username VARCHAR(15) NOT NULL,
  username_actualiza VARCHAR(15) DEFAULT NULL,
  promedio_esc_anterior DOUBLE DEFAULT NULL,
  promedio_ex_admision DOUBLE DEFAULT NULL,
  certificado VARCHAR(50) DEFAULT NULL,
  situacion_certificado TEXT DEFAULT NULL,
  alumno_repetidor TEXT DEFAULT NULL,
  alumno_extemporaneo TEXT DEFAULT NULL,
  alumno_especial TEXT DEFAULT NULL,
  alumno_altainicial SMALLINT DEFAULT NULL,
  alumno_altafinal SMALLINT DEFAULT NULL,
  alumno_altaperiodo SMALLINT DEFAULT NULL,
  ultimo_pago_fecha DATETIME DEFAULT NULL,
  ultimo_pago_caja SMALLINT DEFAULT NULL,
  ultimo_pago_sesioncaja SMALLINT DEFAULT NULL,
  ultimo_pago_recibocaja VARCHAR(15) DEFAULT NULL,
  observaciones_edocuenta VARCHAR(255) DEFAULT NULL,
  id_campus SMALLINT NOT NULL,
  id_promotor VARCHAR(30) DEFAULT NULL,
  id_grupoetnico SMALLINT DEFAULT NULL,
  huella1 LONGBLOB DEFAULT NULL,
  huella2 LONGBLOB DEFAULT NULL,
  huella1_template LONGBLOB DEFAULT NULL,
  huella2_template LONGBLOB DEFAULT NULL,
  fecha_prospeccion DATETIME DEFAULT NULL,
  prospeccion_inicial SMALLINT DEFAULT NULL,
  prospeccion_final SMALLINT DEFAULT NULL,
  prospeccion_periodo SMALLINT DEFAULT NULL,
  prospeccion_aulaescolar TEXT DEFAULT NULL,
  egreso_inicial SMALLINT DEFAULT NULL,
  egreso_final SMALLINT DEFAULT NULL,
  egreso_periodo SMALLINT DEFAULT NULL,
  facturar_regimen_fiscal VARCHAR(10) DEFAULT NULL,
  fecha_actualizacion_kardex DATETIME DEFAULT NULL,
  prospeccion_medio VARCHAR(20) DEFAULT NULL,
  prospeccion_notas VARCHAR(255) DEFAULT NULL,
  fecha_ult_sync_aula DATETIME DEFAULT NULL,
  fecha_ult_sync_cxcs_aula DATETIME DEFAULT NULL,
  fecha_ult_sync_kardex_aula DATETIME DEFAULT NULL,
  categoria_baja VARCHAR(5) DEFAULT NULL,
  ciclobaja_inicial SMALLINT DEFAULT NULL,
  ciclobaja_final SMALLINT DEFAULT NULL,
  ciclobaja_periodo SMALLINT DEFAULT NULL,
  impresiones INT DEFAULT NULL,
  medios_de INT DEFAULT NULL,
  infopro VARCHAR(150) DEFAULT NULL,
  grupovuln VARCHAR(150) DEFAULT NULL,
  padres VARCHAR(150) DEFAULT NULL,
  tramite TEXT DEFAULT NULL,
  faciltramite VARCHAR(150) DEFAULT NULL,
  sugerencia VARCHAR(250) DEFAULT NULL,
  PRIMARY KEY (id_escuela, numeroalumno)
) ENGINE=InnoDB;

CREATE INDEX idx_alumnos_alumnospormatricula ON alumnos (id_escuela, matricula);
CREATE INDEX idx_alumnos_alumnos_byfamilia ON alumnos (id_escuela, id_familia);
CREATE INDEX idx_alumnos_alumnos_byname ON alumnos (id_escuela, paterno, materno, nombre);

-- ======================================================================
-- TABLA: ALUMNOS_KARDEX  |  31 columnas  |  ~1065993 registros
-- ======================================================================
CREATE TABLE alumnos_kardex (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  numeroalumno INT NOT NULL,
  id_plan VARCHAR(10) NOT NULL,
  id_etapa VARCHAR(10) NOT NULL,
  id_tipoeval VARCHAR(255) NOT NULL,
  claveasignatura VARCHAR(15) NOT NULL,
  tipoexamen SMALLINT NOT NULL,
  version SMALLINT NOT NULL,
  id_eval VARCHAR(2) NOT NULL,
  calificacion VARCHAR(10) DEFAULT NULL,
  internaluse TEXT DEFAULT NULL,
  protegido TEXT DEFAULT NULL,
  aux1 INT DEFAULT NULL,
  aux2 INT DEFAULT NULL,
  aux3 INT DEFAULT NULL,
  caux1 VARCHAR(50) DEFAULT NULL,
  caux2 VARCHAR(50) DEFAULT NULL,
  caux3 VARCHAR(50) DEFAULT NULL,
  fecha DATETIME DEFAULT NULL,
  nota TEXT DEFAULT NULL,
  inicial SMALLINT DEFAULT NULL,
  final SMALLINT DEFAULT NULL,
  periodo SMALLINT DEFAULT NULL,
  relacion_cxc TEXT DEFAULT NULL,
  acta VARCHAR(25) DEFAULT NULL,
  literal TEXT DEFAULT NULL,
  registro_aulaescolar TEXT DEFAULT NULL,
  registro_evals TEXT DEFAULT NULL,
  revalida_asignatura TEXT DEFAULT NULL,
  observaciones VARCHAR(255) DEFAULT NULL,
  observaciones_asignatura VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (id_escuela, numeroalumno, id_plan, id_tipoeval, id_etapa, claveasignatura, tipoexamen, version, id_eval)
) ENGINE=InnoDB;

-- ======================================================================
-- TABLA: CURSOS  |  23 columnas  |  ~893 registros
-- ======================================================================
CREATE TABLE cursos (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  inicial SMALLINT NOT NULL,
  final SMALLINT NOT NULL,
  periodo SMALLINT NOT NULL,
  codigo_curso VARCHAR(15) NOT NULL,
  modulo SMALLINT NOT NULL,
  descripcion VARCHAR(150) DEFAULT NULL,
  id_plan VARCHAR(10) DEFAULT NULL,
  id_tipoeval VARCHAR(5) DEFAULT NULL,
  id_etapa VARCHAR(10) DEFAULT NULL,
  claveasignatura VARCHAR(15) DEFAULT NULL,
  version SMALLINT NOT NULL,
  claveprofesor VARCHAR(10) DEFAULT NULL,
  cupomaximo SMALLINT DEFAULT NULL,
  desde DATETIME DEFAULT NULL,
  hasta DATETIME DEFAULT NULL,
  sesiones SMALLINT DEFAULT NULL,
  inscritos SMALLINT DEFAULT NULL,
  suplente VARCHAR(10) DEFAULT NULL,
  turno VARCHAR(2) DEFAULT NULL,
  id_campus SMALLINT DEFAULT NULL,
  fecha_ult_sync_aula DATETIME DEFAULT NULL,
  fecha_ult_sync_als_aula DATETIME DEFAULT NULL,
  PRIMARY KEY (id_escuela, inicial, final, periodo, codigo_curso)
) ENGINE=InnoDB;

CREATE INDEX idx_cursos_cursosindex1 ON cursos (id_escuela, inicial, final, periodo, claveprofesor, codigo_curso);
CREATE INDEX idx_cursos_cursosindex2 ON cursos (id_escuela, id_plan, id_tipoeval, id_etapa, claveasignatura);
CREATE INDEX idx_cursos_cursosindex3 ON cursos (id_escuela, inicial, final, periodo, modulo, codigo_curso);

-- ======================================================================
-- TABLA: CURSOS_DET  |  11 columnas  |  ~2407 registros
-- ======================================================================
CREATE TABLE cursos_det (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  inicial SMALLINT NOT NULL,
  final SMALLINT NOT NULL,
  periodo SMALLINT NOT NULL,
  codigo_curso VARCHAR(15) NOT NULL,
  dia SMALLINT NOT NULL,
  hora_inicial DATETIME NOT NULL,
  hora_final DATETIME NOT NULL,
  id_campus SMALLINT DEFAULT NULL,
  edificio VARCHAR(10) DEFAULT NULL,
  aula VARCHAR(10) DEFAULT NULL,
  PRIMARY KEY (id_escuela, inicial, final, periodo, codigo_curso, dia, hora_inicial, hora_final)
) ENGINE=InnoDB;

CREATE INDEX idx_cursos_det_cursos_detindex1 ON cursos_det (id_escuela, inicial, final, periodo, id_campus, edificio, aula, dia, codigo_curso);
CREATE INDEX idx_cursos_det_cursos_detindex2 ON cursos_det (id_escuela, id_campus, edificio, aula);

-- ======================================================================
-- TABLA: HORARIOS_DET  |  22 columnas  |  ~23613 registros
-- ======================================================================
CREATE TABLE horarios_det (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  inicial SMALLINT NOT NULL,
  final SMALLINT NOT NULL,
  periodo SMALLINT NOT NULL,
  codigo_grupo VARCHAR(15) NOT NULL,
  periodo_ini DATETIME NOT NULL,
  periodo_fin DATETIME NOT NULL,
  dia SMALLINT NOT NULL,
  sesion SMALLINT NOT NULL,
  id_campus SMALLINT NOT NULL,
  edificio VARCHAR(10) NOT NULL,
  aula VARCHAR(10) NOT NULL,
  claveprofesor VARCHAR(10) NOT NULL,
  id_plan VARCHAR(10) DEFAULT NULL,
  id_etapa VARCHAR(10) DEFAULT NULL,
  id_tipoeval VARCHAR(5) DEFAULT NULL,
  claveasignatura VARCHAR(15) DEFAULT NULL,
  hora_inicio DATETIME DEFAULT NULL,
  hora_fin DATETIME DEFAULT NULL,
  horas_teoria_practica DATETIME DEFAULT NULL,
  nota TEXT DEFAULT NULL,
  nota_texto VARCHAR(200) DEFAULT NULL,
  PRIMARY KEY (id_escuela, inicial, final, periodo, codigo_grupo, periodo_ini, periodo_fin, dia, sesion)
) ENGINE=InnoDB;

CREATE INDEX idx_horarios_det_horarios_detindex1 ON horarios_det (id_escuela, inicial, final, periodo, claveprofesor, dia, sesion);
CREATE INDEX idx_horarios_det_horarios_detindex2 ON horarios_det (id_escuela, inicial, final, periodo, id_campus, edificio, aula, dia, sesion);
CREATE INDEX idx_horarios_det_horarios_detindex3 ON horarios_det (id_escuela, inicial, final, periodo, codigo_grupo, id_plan, id_etapa, id_tipoeval, claveasignatura);

-- ======================================================================
-- TABLA: EMPLEADOS  |  64 columnas  |  ~101 registros
-- ======================================================================
CREATE TABLE empleados (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  numempleado VARCHAR(30) NOT NULL,
  nombreempleado VARCHAR(100) DEFAULT NULL,
  fotografia LONGBLOB DEFAULT NULL,
  genero VARCHAR(1) DEFAULT NULL,
  statusactual VARCHAR(2) DEFAULT NULL,
  fecha_ingreso DATETIME DEFAULT NULL,
  fecha_baja DATETIME DEFAULT NULL,
  fecha_nacimiento DATETIME DEFAULT NULL,
  lugar_nacimiento VARCHAR(100) DEFAULT NULL,
  estado_nacimiento VARCHAR(5) DEFAULT NULL,
  nacionalidad VARCHAR(30) DEFAULT NULL,
  estado_civil TEXT DEFAULT NULL,
  numero_seguridadsocial VARCHAR(35) DEFAULT NULL,
  cedula_fiscal VARCHAR(20) DEFAULT NULL,
  clave_ciudadana VARCHAR(30) DEFAULT NULL,
  domicilio VARCHAR(100) DEFAULT NULL,
  cp VARCHAR(10) DEFAULT NULL,
  ciudad VARCHAR(100) DEFAULT NULL,
  estado VARCHAR(5) DEFAULT NULL,
  latitud DOUBLE DEFAULT NULL,
  longitud DOUBLE DEFAULT NULL,
  telefono VARCHAR(30) DEFAULT NULL,
  celular VARCHAR(30) DEFAULT NULL,
  telefono_oficina VARCHAR(30) DEFAULT NULL,
  email VARCHAR(255) DEFAULT NULL,
  nivel VARCHAR(10) DEFAULT NULL,
  departamento VARCHAR(100) DEFAULT NULL,
  cargo VARCHAR(50) DEFAULT NULL,
  promotor TEXT DEFAULT NULL,
  conductor TEXT DEFAULT NULL,
  contrato VARCHAR(10) DEFAULT NULL,
  sueldo DOUBLE DEFAULT NULL,
  trabajo_anterior VARCHAR(50) DEFAULT NULL,
  cargo_anterior VARCHAR(50) DEFAULT NULL,
  nivel_estudios VARCHAR(50) DEFAULT NULL,
  especialidad VARCHAR(50) DEFAULT NULL,
  titulado TEXT DEFAULT NULL,
  institucion_estudios VARCHAR(50) DEFAULT NULL,
  cedula_profesional VARCHAR(50) DEFAULT NULL,
  anotaciones TEXT DEFAULT NULL,
  emergencia_persona VARCHAR(100) DEFAULT NULL,
  emergencia_telefono VARCHAR(30) DEFAULT NULL,
  tarjeta_id VARCHAR(50) DEFAULT NULL,
  huella1 LONGBLOB DEFAULT NULL,
  huella2 LONGBLOB DEFAULT NULL,
  huella1_template LONGBLOB DEFAULT NULL,
  huella2_template LONGBLOB DEFAULT NULL,
  curriculum TEXT DEFAULT NULL,
  fecha_creacion DATETIME DEFAULT NULL,
  fecha_actualizacion DATETIME DEFAULT NULL,
  username VARCHAR(15) NOT NULL,
  username_actualiza VARCHAR(15) DEFAULT NULL,
  nip VARCHAR(50) DEFAULT NULL,
  id_campus SMALLINT DEFAULT NULL,
  clave_categoria VARCHAR(10) DEFAULT NULL,
  forma_pago VARCHAR(10) DEFAULT NULL,
  banco VARCHAR(150) DEFAULT NULL,
  banco_cuenta VARCHAR(20) DEFAULT NULL,
  banco_clabe VARCHAR(20) DEFAULT NULL,
  banco_sucursal VARCHAR(50) DEFAULT NULL,
  pension_alimenticia TEXT DEFAULT NULL,
  pension_alimenticia_benef INT DEFAULT NULL,
  pension_alimenticia_numempleado VARCHAR(30) DEFAULT NULL,
  PRIMARY KEY (id_escuela, numempleado)
) ENGINE=InnoDB;

-- ======================================================================
-- TABLA: EMPLEADOS_ASISTENCIA  |  29 columnas  |  ~15459 registros
-- ======================================================================
CREATE TABLE empleados_asistencia (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  numempleado VARCHAR(30) NOT NULL,
  claveprofesor VARCHAR(10) NOT NULL,
  fecha DATETIME NOT NULL,
  fecha_evento DATETIME NOT NULL,
  dia_entrada SMALLINT NOT NULL,
  evento SMALLINT NOT NULL,
  hora_entrada DATETIME DEFAULT NULL,
  hora_entrada_evento DATETIME DEFAULT NULL,
  dia_salida SMALLINT DEFAULT NULL,
  hora_salida DATETIME DEFAULT NULL,
  hora_salida_evento DATETIME DEFAULT NULL,
  dia_salidaacomer SMALLINT DEFAULT NULL,
  hora_salidaacomer DATETIME DEFAULT NULL,
  hora_salidaacomer_evento DATETIME DEFAULT NULL,
  dia_regresodecomer SMALLINT DEFAULT NULL,
  hora_regresodecomer DATETIME DEFAULT NULL,
  hora_regresodecomer_evento DATETIME DEFAULT NULL,
  horas_variables DATETIME DEFAULT NULL,
  rel_inicial SMALLINT DEFAULT NULL,
  rel_final SMALLINT DEFAULT NULL,
  rel_periodo SMALLINT DEFAULT NULL,
  rel_codigo_grupo VARCHAR(15) DEFAULT NULL,
  rel_codigo_curso VARCHAR(15) DEFAULT NULL,
  rel_id_plan VARCHAR(10) DEFAULT NULL,
  rel_id_etapa VARCHAR(10) DEFAULT NULL,
  rel_id_tipoeval TEXT DEFAULT NULL,
  rel_claveasignatura VARCHAR(15) DEFAULT NULL,
  excepcion VARCHAR(10) DEFAULT NULL,
  PRIMARY KEY (id_escuela, numempleado, claveprofesor, fecha, dia_entrada, evento)
) ENGINE=InnoDB;

-- ======================================================================
-- TABLA: EMPLEADOS_HORARIOS  |  5 columnas  |  ~101 registros
-- ======================================================================
CREATE TABLE empleados_horarios (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  numempleado VARCHAR(30) NOT NULL,
  horario INT NOT NULL,
  fecha_inicial DATETIME NOT NULL,
  fecha_final DATETIME NOT NULL,
  PRIMARY KEY (id_escuela, numempleado, horario, fecha_inicial, fecha_final)
) ENGINE=InnoDB;

-- ======================================================================
-- TABLA: EMPLEADOS_CFGHORARIOS  |  6 columnas  |  ~9 registros
-- ======================================================================
CREATE TABLE empleados_cfghorarios (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  horario INT NOT NULL,
  nombre_horario VARCHAR(50) DEFAULT NULL,
  tipo_horario TEXT DEFAULT NULL,
  tolerancia_entrada SMALLINT DEFAULT NULL,
  tolerancia_regresodecomer SMALLINT DEFAULT NULL,
  PRIMARY KEY (id_escuela, horario)
) ENGINE=InnoDB;

-- ======================================================================
-- TABLA: EMPLEADOS_CFGHORARIOS_DET  |  12 columnas  |  ~40 registros
-- ======================================================================
CREATE TABLE empleados_cfghorarios_det (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  horario INT NOT NULL,
  dia_entrada SMALLINT NOT NULL,
  hora_entrada DATETIME NOT NULL,
  dia_salida SMALLINT NOT NULL,
  hora_salida DATETIME NOT NULL,
  receso_comida TEXT DEFAULT NULL,
  dia_salidaacomer SMALLINT DEFAULT NULL,
  hora_salidaacomer DATETIME DEFAULT NULL,
  dia_regresodecomer SMALLINT DEFAULT NULL,
  hora_regresodecomer DATETIME DEFAULT NULL,
  horas_variables DATETIME DEFAULT NULL,
  PRIMARY KEY (id_escuela, horario, dia_entrada, hora_entrada, dia_salida, hora_salida)
) ENGINE=InnoDB;

-- ======================================================================
-- TABLA: EMPLEADOS_CONTRATOS_CAT  |  6 columnas  |  ~6 registros
-- ======================================================================
CREATE TABLE empleados_contratos_cat (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  contrato VARCHAR(10) NOT NULL,
  descripcion VARCHAR(100) DEFAULT NULL,
  dias_periodo SMALLINT DEFAULT NULL,
  regimen_sat INT DEFAULT NULL,
  contrato_sat INT DEFAULT NULL,
  PRIMARY KEY (id_escuela, contrato)
) ENGINE=InnoDB;

-- ======================================================================
-- TABLA: CFGPLANES_MST  |  46 columnas  |  ~67 registros
-- ======================================================================
CREATE TABLE cfgplanes_mst (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  id_plan VARCHAR(10) NOT NULL,
  nombre_plan VARCHAR(50) NOT NULL,
  nivel VARCHAR(10) DEFAULT NULL,
  minimo DOUBLE DEFAULT NULL,
  maximo DOUBLE DEFAULT NULL,
  minimo_aprobatorio DOUBLE DEFAULT NULL,
  activo TEXT DEFAULT NULL,
  creditos_aprobar DOUBLE DEFAULT NULL,
  creditos_totales DOUBLE DEFAULT NULL,
  verticales_decimales SMALLINT DEFAULT NULL,
  auxname1 VARCHAR(50) DEFAULT NULL,
  auxname2 VARCHAR(50) DEFAULT NULL,
  auxname3 VARCHAR(50) DEFAULT NULL,
  cauxname1 VARCHAR(50) DEFAULT NULL,
  cauxname2 VARCHAR(50) DEFAULT NULL,
  cauxname3 VARCHAR(50) DEFAULT NULL,
  utilizar_periodos TEXT DEFAULT NULL,
  utilizar_fechas TEXT DEFAULT NULL,
  utilizar_nom_opta TEXT DEFAULT NULL,
  utilizar_obs TEXT DEFAULT NULL,
  valornumerico_np DOUBLE DEFAULT NULL,
  valornumerico_sd DOUBLE DEFAULT NULL,
  rotulo_sd VARCHAR(15) DEFAULT NULL,
  rotulo_np VARCHAR(15) DEFAULT NULL,
  condicion_sd VARCHAR(250) DEFAULT NULL,
  obtenerpromedioskardex TEXT DEFAULT NULL,
  verticales_redondear_rep TEXT NOT NULL,
  verticales_redondear_desde DOUBLE DEFAULT NULL,
  ordenarxaux TEXT DEFAULT NULL,
  registro_oficial VARCHAR(50) DEFAULT NULL,
  registro_fecha_aut DATETIME DEFAULT NULL,
  formato_actas VARCHAR(250) DEFAULT NULL,
  asistencia TEXT DEFAULT NULL,
  activado_versiones TEXT DEFAULT NULL,
  version_vigente SMALLINT NOT NULL,
  clave_plan VARCHAR(20) DEFAULT NULL,
  clave_carrera_dgp INT DEFAULT NULL,
  clave_idcarrera INT DEFAULT NULL,
  nombre_carrera VARCHAR(100) DEFAULT NULL,
  clave_idautorizacion INT DEFAULT NULL,
  clave_idservicio_social INT DEFAULT NULL,
  fecha_ult_sync_aula DATETIME DEFAULT NULL,
  fecha_ult_sync_paq_aula DATETIME DEFAULT NULL,
  fecha_ult_sync_evals_aula DATETIME DEFAULT NULL,
  modelo_academico VARCHAR(10) DEFAULT NULL,
  PRIMARY KEY (id_escuela, id_plan)
) ENGINE=InnoDB;

-- ======================================================================
-- TABLA: CFGPLANES_EVAL  |  27 columnas  |  ~711 registros
-- ======================================================================
CREATE TABLE cfgplanes_eval (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  id_plan VARCHAR(10) NOT NULL,
  id_tipoeval VARCHAR(255) NOT NULL,
  claveasignatura VARCHAR(15) NOT NULL,
  version SMALLINT NOT NULL,
  id_eval VARCHAR(2) NOT NULL,
  nombrecorto VARCHAR(10) NOT NULL,
  descripcion VARCHAR(50) NOT NULL,
  ver_en_kardex TEXT DEFAULT NULL,
  ver_en_boletas TEXT DEFAULT NULL,
  ver_en_listas TEXT DEFAULT NULL,
  ver_en_registro TEXT DEFAULT NULL,
  ver_en_graficas TEXT DEFAULT NULL,
  ver_en_aulaescolar TEXT DEFAULT NULL,
  opcional TEXT DEFAULT NULL,
  dependiente VARCHAR(2) DEFAULT NULL,
  dependiente_caso TEXT DEFAULT NULL,
  define_aprueba_reprueba TEXT DEFAULT NULL,
  formula VARCHAR(255) DEFAULT NULL,
  periodo SMALLINT DEFAULT NULL,
  decimales SMALLINT DEFAULT NULL,
  redondear_desde DOUBLE DEFAULT NULL,
  redondear_reprobatorio TEXT DEFAULT NULL,
  reducir TEXT DEFAULT NULL,
  condic_formula VARCHAR(100) DEFAULT NULL,
  ignorar_validacion_rangos TEXT DEFAULT NULL,
  observaciones_formula TEXT DEFAULT NULL,
  PRIMARY KEY (id_escuela, id_plan, id_tipoeval, claveasignatura, version, id_eval)
) ENGINE=InnoDB;

-- ======================================================================
-- TABLA: CFGPLANES_ETAPAS  |  4 columnas  |  ~67 registros
-- ======================================================================
CREATE TABLE cfgplanes_etapas (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  id_plan VARCHAR(10) NOT NULL,
  id_etapa VARCHAR(10) NOT NULL,
  descripcion VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (id_escuela, id_plan, id_etapa)
) ENGINE=InnoDB;

-- ======================================================================
-- TABLA: CFGSTATUS  |  19 columnas  |  ~14 registros
-- ======================================================================
CREATE TABLE cfgstatus (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  tipo VARCHAR(255) NOT NULL,
  status VARCHAR(2) NOT NULL,
  descripcion VARCHAR(50) DEFAULT NULL,
  activo TEXT DEFAULT NULL,
  nuevo_ingreso TEXT DEFAULT NULL,
  baja_temporal TEXT DEFAULT NULL,
  baja_definitiva TEXT DEFAULT NULL,
  cond_motivos_academicos TEXT DEFAULT NULL,
  cond_motivos_admvos TEXT DEFAULT NULL,
  egresados TEXT DEFAULT NULL,
  aspirantes TEXT DEFAULT NULL,
  rechazados TEXT DEFAULT NULL,
  graduados TEXT DEFAULT NULL,
  excluir_de_consultas TEXT DEFAULT NULL,
  excluir_de_proc_admvos TEXT DEFAULT NULL,
  excluir_de_proc_academs TEXT DEFAULT NULL,
  excluir_de_proc_financ TEXT DEFAULT NULL,
  idevento VARCHAR(5) DEFAULT NULL,
  PRIMARY KEY (id_escuela, tipo, status)
) ENGINE=InnoDB;

-- ======================================================================
-- TABLA: CFGAULAS  |  6 columnas  |  ~66 registros
-- ======================================================================
CREATE TABLE cfgaulas (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  id_campus SMALLINT NOT NULL,
  edificio VARCHAR(10) NOT NULL,
  aula VARCHAR(10) NOT NULL,
  descripcion VARCHAR(50) NOT NULL,
  capacidad SMALLINT DEFAULT NULL,
  PRIMARY KEY (id_escuela, id_campus, edificio, aula)
) ENGINE=InnoDB;

-- ======================================================================
-- TABLA: ALUMNOS_NIVELES  |  13 columnas  |  ~23445 registros
-- ======================================================================
CREATE TABLE alumnos_niveles (
  id_escuela INT NOT NULL AUTO_INCREMENT,
  numeroalumno INT NOT NULL,
  inicial SMALLINT NOT NULL,
  final SMALLINT NOT NULL,
  periodo SMALLINT NOT NULL,
  nivel VARCHAR(10) NOT NULL,
  grado SMALLINT DEFAULT NULL,
  subnivel VARCHAR(10) DEFAULT NULL,
  fecha_creacion DATETIME DEFAULT NULL,
  fecha_modificacion DATETIME DEFAULT NULL,
  status VARCHAR(2) DEFAULT NULL,
  matricula VARCHAR(30) DEFAULT NULL,
  id_campus SMALLINT DEFAULT NULL,
  PRIMARY KEY (id_escuela, numeroalumno, inicial, final, periodo, nivel)
) ENGINE=InnoDB;

-- ======================================================================
-- RESUMEN
-- ======================================================================
-- Verificacion de tablas:
--   cfgsedes: 8 cols
--   cfgplanes_det: 45 cols
--   profesores: 65 cols
--   cfgsesiones: 8 cols
--   grupos: 29 cols
--   cfgturnos: 3 cols
--   cfgniveles: 34 cols
--   ciclos: 14 cols
--   alumnos_grupos: 22 cols
--   alumnos: 115 cols
--   alumnos_kardex: 31 cols
--   cursos: 23 cols
--   cursos_det: 11 cols
--   horarios_det: 22 cols
--   empleados: 64 cols
--   empleados_asistencia: 29 cols
--   empleados_horarios: 5 cols
--   empleados_cfghorarios: 6 cols
--   empleados_cfghorarios_det: 12 cols
--   empleados_contratos_cat: 6 cols
--   cfgplanes_mst: 46 cols
--   cfgplanes_eval: 27 cols
--   cfgplanes_etapas: 4 cols
--   cfgstatus: 19 cols
--   cfgaulas: 6 cols
--   alumnos_niveles: 13 cols
-- ======================================================================
-- FIN
-- ======================================================================
