-- =====================================================================
--  Intranet de Soporte Informático - Script de base de datos
--  Archivo: doc/schema.sql
--  Autor:   Pablo Barrera Corbacho
--  Motor:   MySQL 8.0.16+ / MariaDB 10.2+ (InnoDB, utf8mb4)
--
--  Contenido:
--    1. Creación de la base de datos
--    2. Tabla `tecnicos`
--    3. Tabla `incidencias` (con clave foránea a `tecnicos`)
--    4. Datos de prueba
--    5. Consultas de verificación (comentadas)
--
--  Cómo importarlo:
--    - phpMyAdmin: pestaña "Importar" -> seleccionar este archivo.
--    - Consola:    mysql -u root -p < doc/schema.sql
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. Base de datos
-- ---------------------------------------------------------------------
DROP DATABASE IF EXISTS intranet_soporte;

CREATE DATABASE intranet_soporte
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE intranet_soporte;

-- ---------------------------------------------------------------------
-- 2. Tabla de técnicos
-- ---------------------------------------------------------------------
--  `tareas_activas` es el contador que la transacción de
--  "Asignación Avanzada" incrementa en +1 al asignar una incidencia.
--
--  La restricción CHECK limita la carga a un máximo de 5 tareas
--  activas por técnico. Sirve para el caso de prueba 3: si se asigna
--  una incidencia a un técnico que ya tiene 5, el SEGUNDO UPDATE
--  (el del contador) lanza una excepción y la transacción debe hacer
--  rollBack(), deshaciendo también el PRIMER UPDATE de la incidencia.
-- ---------------------------------------------------------------------
CREATE TABLE tecnicos (
    id              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    nombre          VARCHAR(100)     NOT NULL,
    email           VARCHAR(150)     NOT NULL,
    especialidad    VARCHAR(100)     NOT NULL DEFAULT 'General',
    tareas_activas  INT UNSIGNED     NOT NULL DEFAULT 0,
    fecha_alta      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_tecnicos_email (email),
    CONSTRAINT chk_tareas_activas CHECK (tareas_activas <= 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. Tabla de incidencias
-- ---------------------------------------------------------------------
--  - `estado`: Pendiente / En Proceso / Resuelta (editar_incidencia.php).
--  - `prioridad`: Baja / Media / Alta / Urgente (nueva_incidencia.php).
--  - `tecnico_id`: NULL mientras no esté asignada. Si se intenta
--    asignar un técnico que no existe, la clave foránea impide
--    guardar el dato (no hay datos huérfanos).
--  - ON DELETE SET NULL: si se borra un técnico, sus incidencias
--    quedan sin asignar en lugar de borrarse.
-- ---------------------------------------------------------------------
CREATE TABLE incidencias (
    id                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    titulo               VARCHAR(150)  NOT NULL,
    descripcion          TEXT          NOT NULL,
    prioridad            ENUM('Baja', 'Media', 'Alta', 'Urgente')
                                       NOT NULL DEFAULT 'Media',
    email_cliente        VARCHAR(150)  NOT NULL,
    estado               ENUM('Pendiente', 'En Proceso', 'Resuelta')
                                       NOT NULL DEFAULT 'Pendiente',
    tecnico_id           INT UNSIGNED  NULL DEFAULT NULL,
    fecha_creacion       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                       ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_incidencias_estado (estado),
    KEY idx_incidencias_tecnico (tecnico_id),
    CONSTRAINT fk_incidencias_tecnico
        FOREIGN KEY (tecnico_id) REFERENCES tecnicos (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. Datos de prueba
-- ---------------------------------------------------------------------
--  Los contadores `tareas_activas` coinciden con el número de
--  incidencias 'En Proceso' asignadas a cada técnico:
--    Laura  -> 2   |  Javier -> 1   |  Marta -> 0
--    Sergio -> 5   (al límite: se usa para forzar el fallo de la
--                   segunda parte de la transacción)
-- ---------------------------------------------------------------------
INSERT INTO tecnicos (id, nombre, email, especialidad, tareas_activas) VALUES
    (1, 'Laura Gómez Ruiz',     'laura.gomez@soporte.local',   'Redes',               2),
    (2, 'Javier Martín López',  'javier.martin@soporte.local', 'Hardware',            1),
    (3, 'Marta Sánchez Vega',   'marta.sanchez@soporte.local', 'Software y ofimática', 0),
    (4, 'Sergio Navarro Gil',   'sergio.navarro@soporte.local','Sistemas y servidores', 5);

INSERT INTO incidencias (titulo, descripcion, prioridad, email_cliente, estado, tecnico_id) VALUES
    -- Pendientes (sin técnico asignado) -> sirven para probar la asignación
    ('Impresora de recepción no imprime',
     'La impresora HP de recepción muestra el error "atasco de papel" aunque la bandeja está vacía.',
     'Media',   'recepcion@empresa.local',   'Pendiente', NULL),
    ('No hay conexión Wi-Fi en la sala de reuniones',
     'Los portátiles detectan la red pero no obtienen dirección IP en la sala 2.',
     'Alta',    'ana.perez@empresa.local',   'Pendiente', NULL),
    ('Instalar LibreOffice en equipo nuevo',
     'El equipo del nuevo becario necesita la suite ofimática y el lector de PDF.',
     'Baja',    'rrhh@empresa.local',        'Pendiente', NULL),
    ('Correo no sincroniza en el móvil',
     'El cliente de correo del móvil corporativo pide la contraseña continuamente.',
     'Media',   'carlos.ruiz@empresa.local', 'Pendiente', NULL),

    -- En Proceso (asignadas a Laura -> 2 tareas activas)
    ('Switch de la planta 1 reiniciándose',
     'El switch principal de la planta 1 se reinicia varias veces al día y corta la red.',
     'Urgente', 'direccion@empresa.local',   'En Proceso', 1),
    ('Configurar VPN para teletrabajo',
     'Dar de alta la VPN a tres empleados del departamento comercial.',
     'Alta',    'comercial@empresa.local',   'En Proceso', 1),

    -- En Proceso (asignada a Javier -> 1 tarea activa)
    ('Monitor parpadea',
     'El monitor del puesto 14 parpadea al cabo de unos minutos de uso.',
     'Baja',    'lucia.moreno@empresa.local','En Proceso', 2),

    -- En Proceso (asignadas a Sergio -> 5 tareas activas, al límite)
    ('Backup nocturno fallido',
     'La copia de seguridad del servidor de ficheros falló las dos últimas noches.',
     'Urgente', 'sistemas@empresa.local',    'En Proceso', 4),
    ('Disco del servidor al 95 %',
     'El volumen de datos del servidor de aplicaciones está casi lleno.',
     'Alta',    'sistemas@empresa.local',    'En Proceso', 4),
    ('Actualizar certificado SSL de la intranet',
     'El certificado caduca la próxima semana.',
     'Alta',    'sistemas@empresa.local',    'En Proceso', 4),
    ('Revisar logs de accesos fallidos',
     'Se detectan muchos intentos de inicio de sesión fallidos en el servidor.',
     'Media',   'seguridad@empresa.local',   'En Proceso', 4),
    ('Migrar base de datos de pruebas',
     'Mover la base de datos de pruebas al nuevo servidor virtual.',
     'Media',   'desarrollo@empresa.local',  'En Proceso', 4),

    -- Resueltas (no cuentan como tareas activas ni aparecen en el panel)
    ('Ratón inalámbrico sin respuesta',
     'Se cambiaron las pilas y se volvió a emparejar el receptor USB.',
     'Baja',    'pedro.diaz@empresa.local',  'Resuelta', 2),
    ('Contraseña bloqueada',
     'Se desbloqueó la cuenta del usuario y se forzó el cambio de contraseña.',
     'Media',   'maria.lopez@empresa.local', 'Resuelta', 3);

-- ---------------------------------------------------------------------
-- 5. Consultas de verificación (descomentar para probar)
-- ---------------------------------------------------------------------

-- Panel principal: incidencias activas (no resueltas) con su técnico
-- SELECT i.id, i.titulo, i.prioridad, i.estado, i.email_cliente,
--        t.nombre AS tecnico
-- FROM incidencias i
-- LEFT JOIN tecnicos t ON t.id = i.tecnico_id
-- WHERE i.estado <> 'Resuelta'
-- ORDER BY FIELD(i.prioridad, 'Urgente', 'Alta', 'Media', 'Baja'), i.fecha_creacion;

-- Comprobar que los contadores cuadran con las incidencias en proceso
-- SELECT t.id, t.nombre, t.tareas_activas,
--        COUNT(i.id) AS en_proceso_reales
-- FROM tecnicos t
-- LEFT JOIN incidencias i ON i.tecnico_id = t.id AND i.estado = 'En Proceso'
-- GROUP BY t.id, t.nombre, t.tareas_activas;

-- Caso de prueba 3 (manual): la 2ª sentencia falla por el CHECK
-- y el ROLLBACK deja la incidencia 1 como estaba ('Pendiente', sin técnico)
-- START TRANSACTION;
--   UPDATE incidencias SET estado = 'En Proceso', tecnico_id = 4 WHERE id = 1;
--   UPDATE tecnicos SET tareas_activas = tareas_activas + 1 WHERE id = 4;  -- ERROR: chk_tareas_activas
-- ROLLBACK;
-- SELECT id, estado, tecnico_id FROM incidencias WHERE id = 1;
