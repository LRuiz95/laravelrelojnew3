-- ============================================================
-- BASE DE DATOS: rh_dashboard
-- Generada desde rh-dashboard2.php
-- Motor: MySQL 8.0+
-- ============================================================

CREATE DATABASE IF NOT EXISTS rh_dashboard
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE rh_dashboard;

-- ============================================================
-- 1. CATÁLOGOS BASE (sin dependencias)
-- ============================================================

CREATE TABLE cfgsedes (
  ID_CAMPUS       INT NOT NULL,
  DESCRIPCION     VARCHAR(150),
  CIUDAD          VARCHAR(100),
  ESTADO          VARCHAR(100),
  DIRECCION       VARCHAR(255),
  TELEFONO        VARCHAR(30),
  PRIMARY KEY (ID_CAMPUS)
) ENGINE=InnoDB;

CREATE TABLE cfgniveles (
  NIVEL           VARCHAR(10) NOT NULL,
  DESCRIPCION     VARCHAR(100),
  PRIMARY KEY (NIVEL)
) ENGINE=InnoDB;

CREATE TABLE cfgturnos (
  TURNO           VARCHAR(5) NOT NULL,
  DESCRIPCIONTURNO VARCHAR(80),
  PRIMARY KEY (TURNO)
) ENGINE=InnoDB;

CREATE TABLE ciclos (
  INICIAL         INT NOT NULL,
  FINAL           INT NOT NULL,
  PERIODO         INT NOT NULL,
  DESCRIPCION     VARCHAR(150),
  FECHA_INICIAL   DATE,
  FECHA_FINAL     DATE,
  PRIMARY KEY (INICIAL, FINAL, PERIODO)
) ENGINE=InnoDB;

CREATE TABLE cfgstatus (
  STATUS          VARCHAR(5) NOT NULL,
  TIPO            VARCHAR(5) NOT NULL,
  DESCRIPCION     VARCHAR(100),
  PRIMARY KEY (STATUS, TIPO)
) ENGINE=InnoDB;

CREATE TABLE empleados_contratos_cat (
  CONTRATO        VARCHAR(10) NOT NULL,
  DESCRIPCION     VARCHAR(150),
  DIAS_PERIODO    INT,
  PRIMARY KEY (CONTRATO)
) ENGINE=InnoDB;

CREATE TABLE cfgaulas (
  ID_CAMPUS       INT NOT NULL,
  EDIFICIO        VARCHAR(50),
  AULA            VARCHAR(50),
  PRIMARY KEY (ID_CAMPUS, EDIFICIO, AULA),
  CONSTRAINT fk_aulas_sede FOREIGN KEY (ID_CAMPUS) REFERENCES cfgsedes(ID_CAMPUS)
) ENGINE=InnoDB;

-- ============================================================
-- 2. CATÁLOGOS DE PLANES DE ESTUDIO
-- ============================================================

CREATE TABLE cfgplanes_mst (
  ID_PLAN              VARCHAR(20) NOT NULL,
  NOMBRE_PLAN          VARCHAR(200),
  NIVEL                VARCHAR(10),
  CLAVE_PLAN           VARCHAR(30),
  NOMBRE_CARRERA       VARCHAR(200),
  MINIMO_APROBATORIO   DECIMAL(5,2),
  CREDITOS_TOTALES     DECIMAL(6,1),
  ACTIVO               CHAR(1) DEFAULT 'A',
  PRIMARY KEY (ID_PLAN),
  CONSTRAINT fk_planes_nivel FOREIGN KEY (NIVEL) REFERENCES cfgniveles(NIVEL)
) ENGINE=InnoDB;

CREATE TABLE cfgplanes_det (
  ID_PLAN              VARCHAR(20) NOT NULL,
  CLAVEASIGNATURA      VARCHAR(20) NOT NULL,
  NOMBREASIGNATURA     VARCHAR(200),
  NOMBRECORTO          VARCHAR(80),
  CREDITOS             DECIMAL(4,1),
  HORAS_TEORIA         INT,
  HORAS_PRACTICA       INT,
  GRADO                INT,
  AREA                 VARCHAR(50),
  OPCIONAL             CHAR(1) DEFAULT 'N',
  PROMEDIABLE          CHAR(1) DEFAULT 'S',
  ID_ETAPA             VARCHAR(10),
  ID_TIPOEVAL          CHAR(2),
  MINIMO_APROBATORIO   DECIMAL(5,2),
  PRIMARY KEY (ID_PLAN, CLAVEASIGNATURA),
  CONSTRAINT fk_plandet_plan FOREIGN KEY (ID_PLAN) REFERENCES cfgplanes_mst(ID_PLAN)
) ENGINE=InnoDB;

CREATE TABLE cfgplanes_etapas (
  ID_PLAN         VARCHAR(20) NOT NULL,
  ID_ETAPA        VARCHAR(10) NOT NULL,
  DESCRIPCION     VARCHAR(150),
  PRIMARY KEY (ID_PLAN, ID_ETAPA),
  CONSTRAINT fk_etapas_plan FOREIGN KEY (ID_PLAN) REFERENCES cfgplanes_mst(ID_PLAN)
) ENGINE=InnoDB;

CREATE TABLE cfgplanes_eval (
  ID_PLAN                 VARCHAR(20) NOT NULL,
  ID_TIPOEVAL             CHAR(2) NOT NULL,
  VERSION                 INT NOT NULL,
  ID_EVAL                 CHAR(2) NOT NULL,
  NOMBRECORTO             VARCHAR(20),
  DESCRIPCION             VARCHAR(200),
  FORMULA                 VARCHAR(200),
  DEPENDIENTE             CHAR(2),
  DEPENDIENTE_CASO        CHAR(2),
  DEFINE_APRUEBA_REPRUEBA CHAR(1) DEFAULT 'N',
  VER_EN_KARDEX           CHAR(1) DEFAULT 'N',
  PRIMARY KEY (ID_PLAN, ID_TIPOEVAL, VERSION, ID_EVAL),
  CONSTRAINT fk_eval_plan FOREIGN KEY (ID_PLAN) REFERENCES cfgplanes_mst(ID_PLAN)
) ENGINE=InnoDB;

-- ============================================================
-- 3. EMPLEADOS / PROFESORES
-- ============================================================

CREATE TABLE empleados (
  NUMEMPLEADO      VARCHAR(20) NOT NULL,
  NOMBREEMPLEADO   VARCHAR(200),
  DEPARTAMENTO     VARCHAR(100),
  CARGO            VARCHAR(100),
  CONTRATO         VARCHAR(10),
  STATUSACTUAL     CHAR(1) DEFAULT 'A',
  FECHA_INGRESO    DATE,
  ID_CAMPUS        INT,
  NIVEL            VARCHAR(10),
  TARJETA_ID       VARCHAR(30),
  PRIMARY KEY (NUMEMPLEADO),
  INDEX idx_emp_status (STATUSACTUAL),
  INDEX idx_emp_depto (DEPARTAMENTO),
  INDEX idx_emp_sede (ID_CAMPUS),
  INDEX idx_emp_nombre (NOMBREEMPLEADO),
  CONSTRAINT fk_emp_contrato FOREIGN KEY (CONTRATO) REFERENCES empleados_contratos_cat(CONTRATO),
  CONSTRAINT fk_emp_sede FOREIGN KEY (ID_CAMPUS) REFERENCES cfgsedes(ID_CAMPUS)
) ENGINE=InnoDB;

CREATE TABLE profesores (
  CLAVEPROFESOR    VARCHAR(20) NOT NULL,
  NOMBREPROFESOR   VARCHAR(200),
  DEPARTAMENTO     VARCHAR(100),
  CONTRATO         VARCHAR(10),
  STATUSACTUAL     CHAR(1) DEFAULT 'A',
  ORIGEN_HORARIO   CHAR(2),
  FECHA_INGRESO    DATE,
  ID_CAMPUS        INT,
  PRIMARY KEY (CLAVEPROFESOR),
  INDEX idx_prof_status (STATUSACTUAL),
  INDEX idx_prof_origen (ORIGEN_HORARIO),
  INDEX idx_prof_nombre (NOMBREPROFESOR),
  CONSTRAINT fk_prof_contrato FOREIGN KEY (CONTRATO) REFERENCES empleados_contratos_cat(CONTRATO),
  CONSTRAINT fk_prof_sede FOREIGN KEY (ID_CAMPUS) REFERENCES cfgsedes(ID_CAMPUS)
) ENGINE=InnoDB;

-- ============================================================
-- 4. ASISTENCIA DE EMPLEADOS
-- ============================================================

CREATE TABLE empleados_asistencia (
  ID_EVENTO              BIGINT AUTO_INCREMENT,
  NUMEMPLEADO            VARCHAR(20),
  CLAVEPROFESOR          VARCHAR(20),
  FECHA                  DATE,
  FECHA_EVENTO           DATETIME,
  EVENTO                 VARCHAR(10),
  HORA_ENTRADA           TIME,
  HORA_SALIDA            TIME,
  HORA_SALIDAACOMER      TIME,
  HORA_REGRESODECOMER    TIME,
  PRIMARY KEY (ID_EVENTO),
  INDEX idx_asist_fecha (FECHA_EVENTO),
  INDEX idx_asist_emp (NUMEMPLEADO),
  INDEX idx_asist_prof (CLAVEPROFESOR),
  INDEX idx_asist_emp_fecha (NUMEMPLEADO, FECHA_EVENTO),
  INDEX idx_asist_prof_fecha (CLAVEPROFESOR, FECHA_EVENTO),
  CONSTRAINT fk_asist_emp FOREIGN KEY (NUMEMPLEADO) REFERENCES empleados(NUMEMPLEADO),
  CONSTRAINT fk_asist_prof FOREIGN KEY (CLAVEPROFESOR) REFERENCES profesores(CLAVEPROFESOR)
) ENGINE=InnoDB;

-- ============================================================
-- 5. HORARIOS LABORALES (ADMINISTRATIVOS)
-- ============================================================

CREATE TABLE empleados_cfghorarios (
  HORARIO                      VARCHAR(10) NOT NULL,
  NOMBRE_HORARIO               VARCHAR(150),
  TIPO_HORARIO                 CHAR(1),
  HORA_ENTRADA                 TIME,
  HORA_SALIDA                  TIME,
  HORA_COMIDA_INICIO           TIME,
  HORA_COMIDA_FIN              TIME,
  TOLERANCIA_ENTRADA           INT DEFAULT 0,
  TOLERANCIA_REGRESODECOMER    INT DEFAULT 0,
  PRIMARY KEY (HORARIO)
) ENGINE=InnoDB;

CREATE TABLE empleados_cfghorarios_det (
  HORARIO       VARCHAR(10) NOT NULL,
  DIA           INT NOT NULL,
  HORA_INICIO   TIME,
  HORA_FIN      TIME,
  PRIMARY KEY (HORARIO, DIA),
  CONSTRAINT fk_horariodet_horario FOREIGN KEY (HORARIO) REFERENCES empleados_cfghorarios(HORARIO)
) ENGINE=InnoDB;

CREATE TABLE empleados_horarios (
  NUMEMPLEADO    VARCHAR(20) NOT NULL,
  HORARIO        VARCHAR(10) NOT NULL,
  FECHA_INICIAL  DATE,
  FECHA_FINAL    DATE,
  PRIMARY KEY (NUMEMPLEADO, HORARIO),
  INDEX idx_emphor_vigencia (FECHA_INICIAL, FECHA_FINAL),
  CONSTRAINT fk_emphor_emp FOREIGN KEY (NUMEMPLEADO) REFERENCES empleados(NUMEMPLEADO),
  CONSTRAINT fk_emphor_horario FOREIGN KEY (HORARIO) REFERENCES empleados_cfghorarios(HORARIO)
) ENGINE=InnoDB;

-- ============================================================
-- 6. GRUPOS
-- ============================================================

CREATE TABLE grupos (
  CODIGO_GRUPO    VARCHAR(30) NOT NULL,
  GRADO           INT,
  TURNO           VARCHAR(5),
  NIVEL           VARCHAR(10),
  INICIAL         INT NOT NULL,
  FINAL           INT NOT NULL,
  PERIODO         INT NOT NULL,
  INSCRITOS       INT DEFAULT 0,
  CUPO_MAXIMO     INT,
  PRIMARY KEY (CODIGO_GRUPO, INICIAL, FINAL, PERIODO),
  INDEX idx_grupos_ciclo (INICIAL, FINAL, PERIODO),
  INDEX idx_grupos_turno (TURNO),
  INDEX idx_grupos_nivel (NIVEL),
  CONSTRAINT fk_grupos_turno FOREIGN KEY (TURNO) REFERENCES cfgturnos(TURNO),
  CONSTRAINT fk_grupos_nivel FOREIGN KEY (NIVEL) REFERENCES cfgniveles(NIVEL),
  CONSTRAINT fk_grupos_ciclo FOREIGN KEY (INICIAL, FINAL, PERIODO) REFERENCES ciclos(INICIAL, FINAL, PERIODO)
) ENGINE=InnoDB;

-- ============================================================
-- 7. ALUMNOS
-- ============================================================

CREATE TABLE alumnos (
  NUMEROALUMNO    VARCHAR(20) NOT NULL,
  PATERNO         VARCHAR(80),
  MATERNO         VARCHAR(80),
  NOMBRE          VARCHAR(100),
  GENERO          CHAR(1),
  NIVEL           VARCHAR(10),
  GRADO           INT,
  MATRICULA       VARCHAR(20),
  STATUS          CHAR(1) DEFAULT 'A',
  EMAIL           VARCHAR(150),
  CELULAR         VARCHAR(20),
  ID_CAMPUS       INT,
  FECHA_INGRESO   DATE,
  FECHA_BAJA      DATE,
  PRIMARY KEY (NUMEROALUMNO),
  INDEX idx_alumno_nombre (PATERNO, MATERNO, NOMBRE),
  INDEX idx_alumno_matricula (MATRICULA),
  INDEX idx_alumno_status (STATUS),
  INDEX idx_alumno_nivel (NIVEL),
  CONSTRAINT fk_alumno_status FOREIGN KEY (STATUS, 'A') REFERENCES cfgstatus(STATUS, TIPO),
  CONSTRAINT fk_alumno_sede FOREIGN KEY (ID_CAMPUS) REFERENCES cfgsedes(ID_CAMPUS)
) ENGINE=InnoDB;

CREATE TABLE alumnos_niveles (
  NUMEROALUMNO    VARCHAR(20) NOT NULL,
  INICIAL         INT NOT NULL,
  FINAL           INT NOT NULL,
  PERIODO         INT NOT NULL,
  NIVEL           VARCHAR(10),
  GRADO           INT,
  STATUS          CHAR(1),
  PRIMARY KEY (NUMEROALUMNO, INICIAL, FINAL, PERIODO),
  INDEX idx_alniv_ciclo (INICIAL, FINAL, PERIODO),
  CONSTRAINT fk_alniv_alumno FOREIGN KEY (NUMEROALUMNO) REFERENCES alumnos(NUMEROALUMNO),
  CONSTRAINT fk_alniv_ciclo FOREIGN KEY (INICIAL, FINAL, PERIODO) REFERENCES ciclos(INICIAL, FINAL, PERIODO)
) ENGINE=InnoDB;

-- ============================================================
-- 8. INSCRIPCIONES (ALUMNOS POR GRUPO)
-- ============================================================

CREATE TABLE alumnos_grupos (
  NUMEROALUMNO     VARCHAR(20) NOT NULL,
  CODIGO_GRUPO     VARCHAR(30) NOT NULL,
  INICIAL          INT NOT NULL,
  FINAL            INT NOT NULL,
  PERIODO          INT NOT NULL,
  CLAVEASIGNATURA  VARCHAR(20),
  FECHA            DATE,
  REINSCRITO       CHAR(1),
  ID_TIPOEVAL      CHAR(2),
  ID_PLAN          VARCHAR(20),
  PRIMARY KEY (NUMEROALUMNO, CODIGO_GRUPO, INICIAL, FINAL, PERIODO),
  INDEX idx_ag_ciclo (INICIAL, FINAL, PERIODO),
  INDEX idx_ag_grupo (CODIGO_GRUPO),
  INDEX idx_ag_plan (ID_PLAN),
  CONSTRAINT fk_ag_alumno FOREIGN KEY (NUMEROALUMNO) REFERENCES alumnos(NUMEROALUMNO),
  CONSTRAINT fk_ag_grupo FOREIGN KEY (CODIGO_GRUPO, INICIAL, FINAL, PERIODO) REFERENCES grupos(CODIGO_GRUPO, INICIAL, FINAL, PERIODO),
  CONSTRAINT fk_ag_plan FOREIGN KEY (ID_PLAN) REFERENCES cfgplanes_mst(ID_PLAN)
) ENGINE=InnoDB;

-- ============================================================
-- 9. HORARIOS DE CLASE (HORARIOS_DET)
-- ============================================================

CREATE TABLE horarios_det (
  INICIAL                  INT NOT NULL,
  FINAL                    INT NOT NULL,
  PERIODO                  INT NOT NULL,
  CODIGO_GRUPO             VARCHAR(30) NOT NULL,
  CLAVEPROFESOR            VARCHAR(20),
  CLAVEASIGNATURA          VARCHAR(20),
  DIA                      INT,
  HORA_INICIO              TIME,
  HORA_FIN                 TIME,
  ID_CAMPUS                INT,
  EDIFICIO                 VARCHAR(50),
  AULA                     VARCHAR(50),
  SESION                   VARCHAR(10),
  HORAS_TEORIA_PRACTICA    DECIMAL(4,1),
  ID_PLAN                  VARCHAR(20),
  PRIMARY KEY (INICIAL, FINAL, PERIODO, CODIGO_GRUPO, CLAVEASIGNATURA, DIA),
  INDEX idx_hd_ciclo (INICIAL, FINAL, PERIODO),
  INDEX idx_hd_profesor (CLAVEPROFESOR),
  INDEX idx_hd_grupo (CODIGO_GRUPO),
  INDEX idx_hd_aula (EDIFICIO, AULA, DIA),
  INDEX idx_hd_sesion (SESION),
  INDEX idx_hd_dia (DIA),
  INDEX idx_hd_asignatura (CLAVEASIGNATURA),
  CONSTRAINT fk_hd_ciclo FOREIGN KEY (INICIAL, FINAL, PERIODO) REFERENCES ciclos(INICIAL, FINAL, PERIODO),
  CONSTRAINT fk_hd_profesor FOREIGN KEY (CLAVEPROFESOR) REFERENCES profesores(CLAVEPROFESOR),
  CONSTRAINT fk_hd_grupo FOREIGN KEY (CODIGO_GRUPO, INICIAL, FINAL, PERIODO) REFERENCES grupos(CODIGO_GRUPO, INICIAL, FINAL, PERIODO),
  CONSTRAINT fk_hd_plan FOREIGN KEY (ID_PLAN) REFERENCES cfgplanes_mst(ID_PLAN)
) ENGINE=InnoDB;

-- ============================================================
-- 10. HORARIOS DE SESIONES (BASE POR NIVEL/TURNO)
-- ============================================================

CREATE TABLE cfgsesiones (
  SESION         VARCHAR(10) NOT NULL,
  NIVEL          VARCHAR(10) NOT NULL,
  TURNO          VARCHAR(5) NOT NULL,
  HORA_INICIO    TIME,
  HORA_FIN       TIME,
  RECESO         CHAR(1) DEFAULT 'N',
  DESCRIPCION    VARCHAR(100),
  PRIMARY KEY (SESION, NIVEL, TURNO),
  INDEX idx_ses_nivel (NIVEL),
  INDEX idx_ses_turno (TURNO),
  CONSTRAINT fk_ses_nivel FOREIGN KEY (NIVEL) REFERENCES cfgniveles(NIVEL),
  CONSTRAINT fk_ses_turno FOREIGN KEY (TURNO) REFERENCES cfgturnos(TURNO)
) ENGINE=InnoDB;

-- ============================================================
-- 11. CURSOS / MÓDULOS
-- ============================================================

CREATE TABLE cursos (
  CODIGO_CURSO       VARCHAR(30) NOT NULL,
  INICIAL            INT NOT NULL,
  FINAL              INT NOT NULL,
  PERIODO            INT NOT NULL,
  CLAVEASIGNATURA    VARCHAR(20),
  CLAVEPROFESOR      VARCHAR(20),
  DESCRIPCION        VARCHAR(200),
  TURNO              VARCHAR(5),
  ID_CAMPUS          INT,
  INSCRITOS          INT DEFAULT 0,
  ID_PLAN            VARCHAR(20),
  PRIMARY KEY (CODIGO_CURSO, INICIAL, FINAL, PERIODO),
  INDEX idx_cur_ciclo (INICIAL, FINAL, PERIODO),
  INDEX idx_cur_profesor (CLAVEPROFESOR),
  INDEX idx_cur_turno (TURNO),
  INDEX idx_cur_sede (ID_CAMPUS),
  CONSTRAINT fk_cur_ciclo FOREIGN KEY (INICIAL, FINAL, PERIODO) REFERENCES ciclos(INICIAL, FINAL, PERIODO),
  CONSTRAINT fk_cur_profesor FOREIGN KEY (CLAVEPROFESOR) REFERENCES profesores(CLAVEPROFESOR),
  CONSTRAINT fk_cur_turno FOREIGN KEY (TURNO) REFERENCES cfgturnos(TURNO),
  CONSTRAINT fk_cur_sede FOREIGN KEY (ID_CAMPUS) REFERENCES cfgsedes(ID_CAMPUS),
  CONSTRAINT fk_cur_plan FOREIGN KEY (ID_PLAN) REFERENCES cfgplanes_mst(ID_PLAN)
) ENGINE=InnoDB;

CREATE TABLE cursos_det (
  CODIGO_CURSO    VARCHAR(30) NOT NULL,
  INICIAL         INT NOT NULL,
  FINAL           INT NOT NULL,
  PERIODO         INT NOT NULL,
  DIA             INT,
  HORA_INICIAL    TIME,
  HORA_FINAL      TIME,
  EDIFICIO        VARCHAR(50),
  AULA            VARCHAR(50),
  PRIMARY KEY (CODIGO_CURSO, INICIAL, FINAL, PERIODO, DIA),
  CONSTRAINT fk_curdet_curso FOREIGN KEY (CODIGO_CURSO, INICIAL, FINAL, PERIODO) REFERENCES cursos(CODIGO_CURSO, INICIAL, FINAL, PERIODO)
) ENGINE=InnoDB;

-- ============================================================
-- 12. KARDEX (CALIFICACIONES)
-- ============================================================

CREATE TABLE alumnos_kardex (
  NUMEROALUMNO     VARCHAR(20) NOT NULL,
  INICIAL          INT NOT NULL,
  FINAL            INT NOT NULL,
  PERIODO          INT NOT NULL,
  CLAVEASIGNATURA  VARCHAR(20) NOT NULL,
  ID_EVAL          CHAR(2) NOT NULL,
  CALIFICACION     DECIMAL(5,2),
  TIPOEXAMEN       INT,
  VERSION          INT,
  LITERAL          CHAR(1),
  ID_TIPOEVAL      CHAR(2),
  ID_PLAN          VARCHAR(20),
  PRIMARY KEY (NUMEROALUMNO, INICIAL, FINAL, PERIODO, CLAVEASIGNATURA, ID_EVAL),
  INDEX idx_kdx_ciclo (INICIAL, FINAL, PERIODO),
  INDEX idx_kdx_alumno (NUMEROALUMNO),
  INDEX idx_kdx_asignatura (CLAVEASIGNATURA),
  CONSTRAINT fk_kdx_alumno FOREIGN KEY (NUMEROALUMNO) REFERENCES alumnos(NUMEROALUMNO),
  CONSTRAINT fk_kdx_ciclo FOREIGN KEY (INICIAL, FINAL, PERIODO) REFERENCES ciclos(INICIAL, FINAL, PERIODO),
  CONSTRAINT fk_kdx_plan FOREIGN KEY (ID_PLAN) REFERENCES cfgplanes_mst(ID_PLAN)
) ENGINE=InnoDB;
