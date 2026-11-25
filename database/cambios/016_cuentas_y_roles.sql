-- =====================================================================================
-- 016 - Seguridad: cuentas de base por rol (mínimo privilegio, RBAC)
-- =====================================================================================
-- QUÉ:
--   * 3 roles de base: rol_login, rol_operador y rol_admin (rol_admin hereda rol_operador).
--   * 3 cuentas para la aplicación, una por rol: logitrack_login, logitrack_operador y
--     logitrack_admin (solo desde localhost). Se crean BLOQUEADAS (ACCOUNT LOCK) y sin
--     contraseña: database/configurar_cuentas.php les pone una contraseña al azar, las
--     desbloquea y la guarda en config/credenciales.php (que no se versiona).
--
-- POR QUÉ:
--   * Antes la app se conectaba como root sin contraseña (ALL PRIVILEGES WITH GRANT OPTION):
--     un agujero en PHP permitía DROP DATABASE o leer mysql.user. Ahora PHP usa la cuenta
--     del rol de la sesión y la base es una SEGUNDA BARRERA: aunque PHP fallara, un operador
--     no puede borrar una sucursal ni dar de baja un cliente.
--   * Separación de funciones: ninguna cuenta de la app tiene CREATE, DROP, ALTER, INDEX,
--     TRIGGER, GRANT OPTION ni ALL. La estructura (DDL) solo la cambia root.
--
-- CÓMO ESTÁ ARMADO:
--   * Privilegios de OBJETO: SELECT/INSERT/UPDATE/DELETE por tabla, y por COLUMNA donde
--     hace falta (ej.: el operador edita un cliente pero no puede tocar la columna activo).
--   * EXECUTE sobre procedimientos y funciones. Los envíos, los estados del historial y las
--     asignaciones a viajes se crean SOLO por procedimiento: las cuentas no tienen INSERT
--     sobre envio, historial_estado ni viaje_envio.
--   * Los triggers, procedimientos y vistas son SQL SECURITY DEFINER (dueño root@localhost):
--     corren con los permisos del dueño. Por eso la auditoría y las validaciones funcionan
--     sin darle a nadie permisos sobre auditoria ni sobre los procedimientos de validación.
--   * Nadie tiene UPDATE ni DELETE sobre historial_estado ni sobre auditoria: inmutables
--     también por permisos (el atajo @mantenimiento no les sirve a las cuentas de la app).
--   * Privilegio de SISTEMA: solo PROCESS para rol_admin (la pantalla Sesiones lista todas
--     las conexiones de MariaDB).
--
-- Se puede volver a correr: primero borra lo que haya creado una corrida anterior.
-- Comprobar: SHOW GRANTS FOR rol_operador;  SHOW GRANTS FOR 'logitrack_operador'@'localhost';
-- =====================================================================================

DROP USER IF EXISTS 'logitrack_login'@'localhost', 'logitrack_operador'@'localhost', 'logitrack_admin'@'localhost';
DROP ROLE IF EXISTS rol_login;
DROP ROLE IF EXISTS rol_operador;
DROP ROLE IF EXISTS rol_admin;

CREATE ROLE rol_login;
CREATE ROLE rol_operador;
CREATE ROLE rol_admin;

-- -------------------------------------------------------------------------------------
-- rol_login: lo único que necesita la pantalla de login (todavía no se sabe quién es).
-- -------------------------------------------------------------------------------------
GRANT SELECT (usuario_id, username, password_hash, rol, activo) ON logitrack.usuario TO rol_login;
GRANT EXECUTE ON PROCEDURE logitrack.procedimiento_iniciar_sesion TO rol_login;

-- -------------------------------------------------------------------------------------
-- rol_operador: operación diaria. Carga y corrige; no da de baja; recursos y catálogos
-- solo en consulta. (Misma tabla que config/permisos.php.)
-- -------------------------------------------------------------------------------------
-- Consulta
GRANT SELECT ON logitrack.envio            TO rol_operador;
GRANT SELECT ON logitrack.historial_estado TO rol_operador;
GRANT SELECT ON logitrack.viaje            TO rol_operador;
GRANT SELECT ON logitrack.viaje_envio      TO rol_operador;
GRANT SELECT ON logitrack.incidente        TO rol_operador;
GRANT SELECT ON logitrack.cliente          TO rol_operador;
GRANT SELECT ON logitrack.direccion        TO rol_operador;
GRANT SELECT ON logitrack.sucursal         TO rol_operador;
GRANT SELECT ON logitrack.chofer           TO rol_operador;
GRANT SELECT ON logitrack.vehiculo         TO rol_operador;
GRANT SELECT ON logitrack.provincia        TO rol_operador;
GRANT SELECT ON logitrack.localidad        TO rol_operador;
GRANT SELECT ON logitrack.tipo_contenido   TO rol_operador;
GRANT SELECT ON logitrack.tipo_vehiculo    TO rol_operador;
GRANT SELECT ON logitrack.estado_envio     TO rol_operador;
GRANT SELECT ON logitrack.tipo_incidente   TO rol_operador;
GRANT SELECT ON logitrack.tipo_licencia    TO rol_operador;
GRANT SELECT ON logitrack.vista_envios_detalle      TO rol_operador;
GRANT SELECT ON logitrack.vista_historial_completo  TO rol_operador;
GRANT SELECT ON logitrack.vista_envios_pendientes   TO rol_operador;
GRANT SELECT ON logitrack.vista_viajes_en_curso     TO rol_operador;
GRANT SELECT ON logitrack.vista_viajes_programados  TO rol_operador;
GRANT SELECT ON logitrack.vista_clientes_con_envios TO rol_operador;
GRANT SELECT ON logitrack.vista_sucursal_ubicacion  TO rol_operador;

-- Altas y ediciones (UPDATE por columna: sin la columna activo = no puede dar de baja)
GRANT UPDATE (fecha_envio, peso_kg, tipo_cont_cod, remitente_dni, destinatario_dni,
              sucursal_orig_cod, sucursal_dest_cod, direccion_entrega_id)
      ON logitrack.envio TO rol_operador;
GRANT INSERT ON logitrack.cliente TO rol_operador;
GRANT UPDATE (nombre, apellido, mail, telefono, localidad_cod) ON logitrack.cliente TO rol_operador;
GRANT INSERT ON logitrack.direccion TO rol_operador;
GRANT UPDATE (calle_y_numero, localidad_cod, cliente_dni) ON logitrack.direccion TO rol_operador;
GRANT INSERT ON logitrack.viaje TO rol_operador;
GRANT UPDATE (fecha_salida, fecha_llegada, sucursal_origen_cod, sucursal_destino_cod,
              vehiculo_patente, chofer_legajo)
      ON logitrack.viaje TO rol_operador;
GRANT INSERT ON logitrack.incidente TO rol_operador;
GRANT UPDATE (viaje_id, fecha_hora, tipo_incidente, descripcion) ON logitrack.incidente TO rol_operador;
GRANT DELETE ON logitrack.viaje_envio TO rol_operador;   -- quitar un envío de un viaje que no salió

-- Lógica de negocio: solo por procedimientos y funciones
GRANT EXECUTE ON PROCEDURE logitrack.procedimiento_crear_envio            TO rol_operador;
GRANT EXECUTE ON PROCEDURE logitrack.procedimiento_registrar_estado_envio TO rol_operador;
GRANT EXECUTE ON PROCEDURE logitrack.procedimiento_asignar_envio_a_viaje  TO rol_operador;
GRANT EXECUTE ON PROCEDURE logitrack.procedimiento_cerrar_viaje           TO rol_operador;
GRANT EXECUTE ON PROCEDURE logitrack.procedimiento_buscar_rutas           TO rol_operador;
GRANT EXECUTE ON PROCEDURE logitrack.procedimiento_resumen_por_sucursal   TO rol_operador;
GRANT EXECUTE ON FUNCTION  logitrack.funcion_carga_viaje                  TO rol_operador;
GRANT EXECUTE ON FUNCTION  logitrack.funcion_ultimo_estado                TO rol_operador;
GRANT EXECUTE ON FUNCTION  logitrack.funcion_envio_terminado              TO rol_operador;

-- Su propia sesión: controlarla en cada pedido y cerrarla (logout / vencimiento).
-- Por columna: no ve la IP ni el navegador de las sesiones de otros.
GRANT SELECT (sesion_id, token_hash, fin, motivo_cierre) ON logitrack.sesion TO rol_operador;
GRANT UPDATE (ultimo_acceso) ON logitrack.sesion TO rol_operador;
GRANT EXECUTE ON FUNCTION  logitrack.funcion_sesion_activa                 TO rol_operador;
GRANT EXECUTE ON PROCEDURE logitrack.procedimiento_cerrar_sesion           TO rol_operador;
GRANT EXECUTE ON PROCEDURE logitrack.procedimiento_cerrar_sesiones_vencidas TO rol_operador;

-- -------------------------------------------------------------------------------------
-- rol_admin: todo lo del operador (jerarquía de roles) + bajas, recursos, catálogos,
-- usuarios, sesiones y auditoría.
-- -------------------------------------------------------------------------------------
GRANT rol_operador TO rol_admin;

GRANT UPDATE ON logitrack.envio     TO rol_admin;   -- incluye activo: baja y reactivación
GRANT UPDATE ON logitrack.cliente   TO rol_admin;
GRANT UPDATE ON logitrack.direccion TO rol_admin;
GRANT DELETE ON logitrack.viaje     TO rol_admin;
GRANT DELETE ON logitrack.incidente TO rol_admin;
GRANT INSERT, UPDATE         ON logitrack.sucursal       TO rol_admin;   -- baja lógica (sin DELETE)
GRANT INSERT, UPDATE         ON logitrack.chofer         TO rol_admin;
GRANT INSERT, UPDATE         ON logitrack.vehiculo       TO rol_admin;
GRANT INSERT, UPDATE, DELETE ON logitrack.provincia      TO rol_admin;   -- baja física
GRANT INSERT, UPDATE, DELETE ON logitrack.localidad      TO rol_admin;
GRANT INSERT, UPDATE, DELETE ON logitrack.tipo_contenido TO rol_admin;
GRANT INSERT, UPDATE, DELETE ON logitrack.tipo_vehiculo  TO rol_admin;
GRANT SELECT, INSERT, UPDATE ON logitrack.usuario        TO rol_admin;   -- baja lógica (sin DELETE)
GRANT SELECT ON logitrack.sesion                  TO rol_admin;          -- todas las columnas
GRANT SELECT ON logitrack.vista_sesiones_abiertas TO rol_admin;
GRANT SELECT ON logitrack.auditoria               TO rol_admin;          -- solo lectura
GRANT PROCESS ON *.* TO rol_admin;                                       -- privilegio de sistema

-- -------------------------------------------------------------------------------------
-- Cuentas de la aplicación: solo desde esta PC, bloqueadas hasta tener contraseña.
-- -------------------------------------------------------------------------------------
CREATE USER 'logitrack_login'@'localhost'    ACCOUNT LOCK;
CREATE USER 'logitrack_operador'@'localhost' ACCOUNT LOCK;
CREATE USER 'logitrack_admin'@'localhost'    ACCOUNT LOCK;

GRANT rol_login    TO 'logitrack_login'@'localhost';
GRANT rol_operador TO 'logitrack_operador'@'localhost';
GRANT rol_admin    TO 'logitrack_admin'@'localhost';

-- Sin esto, en MariaDB el rol está asignado pero NO activo al conectarse.
SET DEFAULT ROLE rol_login    FOR 'logitrack_login'@'localhost';
SET DEFAULT ROLE rol_operador FOR 'logitrack_operador'@'localhost';
SET DEFAULT ROLE rol_admin    FOR 'logitrack_admin'@'localhost';

-- Con GRANT/CREATE USER no hace falta; solo si se editan las tablas mysql.* a mano.
FLUSH PRIVILEGES;
