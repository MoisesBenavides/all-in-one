-- CREACIÓN DE ROLES
CREATE ROLE 'admin_rol';
CREATE ROLE 'cliente';
CREATE ROLE 'gerente';
CREATE ROLE 'ejecutivo';
CREATE ROLE 'cajero';
CREATE ROLE 'jefe_diagnostico';
CREATE ROLE 'jefe_taller';
CREATE ROLE 'valet_parking';

-- PERMISOS
-- admin
GRANT ALL PRIVILEGES ON *.* TO 'admin_rol' WITH GRANT OPTION;

-- cliente
GRANT SELECT, INSERT, UPDATE, DELETE ON aio_db.cliente TO 'cliente';
GRANT SELECT, INSERT, UPDATE, DELETE ON aio_db.tiene TO 'cliente';
GRANT SELECT, INSERT, UPDATE ON aio_db.vehiculo TO 'cliente';

GRANT SELECT, INSERT ON aio_db.servicio TO 'cliente';
GRANT SELECT, INSERT ON aio_db.taller TO 'cliente';
GRANT SELECT, INSERT ON aio_db.parking TO 'cliente';
GRANT SELECT, INSERT ON aio_db.numero_plaza TO 'cliente';

GRANT SELECT, INSERT, UPDATE ON aio_db.orden TO 'cliente';
GRANT SELECT, INSERT ON aio_db.detalle_orden_producto TO 'cliente';
GRANT SELECT, INSERT ON aio_db.detalle_orden_servicio TO 'cliente';

GRANT SELECT ON aio_db.producto TO 'cliente';
GRANT SELECT ON aio_db.neumatico TO 'cliente';
GRANT SELECT ON aio_db.otro_producto TO 'cliente';

-- gerente
GRANT SELECT, INSERT, UPDATE, DELETE ON aio_db.* TO 'gerente';

-- ejecutivo
--El ejecutivo puede ingresar la marca, modelo y color de un vehículo
GRANT SELECT, UPDATE ON aio_db.vehiculo TO 'ejecutivo';
GRANT SELECT, INSERT, UPDATE ON aio_db.realiza TO 'ejecutivo';
GRANT SELECT, UPDATE ON aio_db.servicio TO 'ejecutivo';
GRANT SELECT, UPDATE ON aio_db.taller TO 'ejecutivo';

-- cajero
GRANT SELECT, INSERT, UPDATE ON aio_db.orden TO 'cajero';
GRANT SELECT, INSERT ON aio_db.detalle_orden_producto TO 'cajero';
GRANT SELECT, INSERT ON aio_db.detalle_orden_servicio TO 'cajero';

GRANT SELECT ON aio_db.producto TO 'cajero';
GRANT SELECT ON aio_db.neumatico TO 'cajero';
GRANT SELECT ON aio_db.otro_producto TO 'cajero';

GRANT SELECT, INSERT ON aio_db.servicio TO 'cajero';
GRANT SELECT, INSERT ON aio_db.taller TO 'cajero';
GRANT SELECT, INSERT ON aio_db.parking TO 'cajero';
GRANT SELECT, INSERT ON aio_db.numero_plaza TO 'cajero';

-- jefe de diagnosticos
GRANT SELECT, UPDATE ON aio_db.servicio TO 'jefe_diagnostico';
GRANT SELECT, UPDATE ON aio_db.taller TO 'jefe_diagnostico';
GRANT SELECT ON aio_db.realiza TO 'jefe_diagnostico';

GRANT SELECT, INSERT, UPDATE, DELETE ON aio_db.ejecutivo TO 'jefe_diagnostico';

-- jefe de taller
GRANT SELECT ON aio_db.servicio TO 'jefe_taller';
GRANT SELECT ON aio_db.taller TO 'jefe_taller';
GRANT SELECT ON aio_db.realiza TO 'jefe_taller';

GRANT SELECT, INSERT, UPDATE, DELETE ON aio_db.ejecutivo TO 'jefe_taller';

-- valet parking
GRANT SELECT, INSERT, UPDATE ON aio_db.vehiculo TO 'valet_parking';
GRANT SELECT, INSERT, UPDATE ON aio_db.servicio TO 'valet_parking';
GRANT SELECT, INSERT, UPDATE ON aio_db.parking TO 'valet_parking';
GRANT SELECT, INSERT, UPDATE ON aio_db.numero_plaza TO 'valet_parking';

-- CREACIÓN DE USUARIOS
CREATE USER 'admin'@'localhost' IDENTIFIED BY 'password_admin';
CREATE USER 'def_cliente'@'localhost' IDENTIFIED BY 'password_cliente';
CREATE USER 'def_gerente'@'localhost' IDENTIFIED BY 'password_gerente';
CREATE USER 'def_ejecutivo'@'localhost' IDENTIFIED BY 'password_ejecutivo';
CREATE USER 'def_cajero'@'localhost' IDENTIFIED BY 'password_cajero';
CREATE USER 'def_jefe_diagnostico'@'localhost' IDENTIFIED BY 'password_jefe_diagnostico';
CREATE USER 'def_jefe_taller'@'localhost' IDENTIFIED BY 'password_jefe_taller';
CREATE USER 'def_valet_parking'@'localhost' IDENTIFIED BY 'password_valet_parking';

-- ASIGNACIÓN DE ROLES A USUARIOS
GRANT 'admin_rol' TO 'admin'@'localhost';
GRANT 'cliente' TO 'def_cliente'@'localhost';
GRANT 'gerente' TO 'def_gerente'@'localhost';
GRANT 'ejecutivo' TO 'def_ejecutivo'@'localhost';
GRANT 'cajero' TO 'def_cajero'@'localhost';
GRANT 'jefe_diagnostico' TO 'def_jefe_diagnostico'@'localhost';
GRANT 'jefe_taller' TO 'def_jefe_taller'@'localhost';
GRANT 'valet_parking' TO 'def_valet_parking'@'localhost';

-- ACTIVACIÓN DE ROLES
SET ROLE 'admin_rol';
SET ROLE 'cliente';
SET ROLE 'gerente';
SET ROLE 'ejecutivo';
SET ROLE 'cajero';
SET ROLE 'jefe_diagnostico';
SET ROLE 'jefe_taller';
SET ROLE 'valet_parking';
