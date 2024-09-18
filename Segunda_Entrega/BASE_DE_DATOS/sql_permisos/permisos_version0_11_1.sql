-- CREACION DE ROLES
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
GRANT SELECT, INSERT, UPDATE, DELETE ON aio.cliente TO 'cliente';
GRANT SELECT, INSERT, UPDATE, DELETE ON aio.tiene TO 'cliente';
GRANT SELECT, INSERT, UPDATE ON aio.vehiculo TO 'cliente';

GRANT SELECT, INSERT ON aio.servicio TO 'cliente';
GRANT SELECT, INSERT ON aio.taller TO 'cliente';
GRANT SELECT, INSERT ON aio.parking TO 'cliente';
GRANT SELECT, INSERT ON aio.numero_plaza TO 'cliente';

GRANT SELECT, INSERT, UPDATE ON aio.orden TO 'cliente';
GRANT SELECT, INSERT ON aio.detalle_orden_producto TO 'cliente';
GRANT SELECT, INSERT ON aio.detalle_orden_servicio TO 'cliente';

GRANT SELECT ON aio.producto TO 'cliente';
GRANT SELECT ON aio.neumatico TO 'cliente';
GRANT SELECT ON aio.otro_producto TO 'cliente';

-- gerente
GRANT SELECT, INSERT, UPDATE, DELETE ON aio.* TO 'gerente';

-- ejecutivo
/*El ejecutivo puede ingresar la marca, modelo y color de un vehículo*/
GRANT SELECT, UPDATE ON aio.vehiculo TO 'ejecutivo';

GRANT SELECT, INSERT, UPDATE ON aio.realiza TO 'ejecutivo';
GRANT SELECT, UPDATE ON aio.servicio TO 'ejecutivo';
GRANT SELECT, UPDATE ON aio.taller TO 'ejecutivo';

-- cajero
GRANT SELECT, INSERT, UPDATE ON aio.orden TO 'cajero';
GRANT SELECT, INSERT ON aio.detalle_orden_producto TO 'cajero';
GRANT SELECT, INSERT ON aio.detalle_orden_servicio TO 'cajero';

GRANT SELECT ON aio.producto TO 'cajero';
GRANT SELECT ON aio.neumatico TO 'cajero';
GRANT SELECT ON aio.otro_producto TO 'cajero';

GRANT SELECT, INSERT ON aio.servicio TO 'cajero';
GRANT SELECT, INSERT ON aio.taller TO 'cajero';
GRANT SELECT, INSERT ON aio.parking TO 'cajero';
GRANT SELECT, INSERT ON aio.numero_plaza TO 'cajero';

-- jefe de diagnosticos
GRANT SELECT, UPDATE ON aio.servicio TO 'jefe_diagnostico';
GRANT SELECT, UPDATE ON aio.taller TO 'jefe_diagnostico';
GRANT SELECT ON aio.realiza TO 'jefe_diagnostico';

GRANT SELECT, INSERT, UPDATE, DELETE ON aio.ejecutivo TO 'jefe_diagnostico';

-- jefe de taller
GRANT SELECT ON aio.servicio TO 'jefe_taller';
GRANT SELECT ON aio.taller TO 'jefe_taller';
GRANT SELECT ON aio.realiza TO 'jefe_taller';

GRANT SELECT, INSERT, UPDATE, DELETE ON aio.ejecutivo TO 'jefe_taller';


-- valet parking
GRANT SELECT, INSERT, UPDATE ON aio.vehiculo TO 'valet_parking';

GRANT SELECT, INSERT, UPDATE ON aio.servicio TO 'valet_parking';
GRANT SELECT, INSERT, UPDATE ON aio.parking TO 'valet_parking';
GRANT SELECT, INSERT, UPDATE ON aio.numero_plaza TO 'valet_parking';

-- SET ROLES
SET ROLE 'admin_rol';
SET ROLE 'cliente';
SET ROLE 'gerente';
SET ROLE 'ejecutivo';
SET ROLE 'cajero';
SET ROLE 'jefe_diagnostico';
SET ROLE 'jefe_taller';
SET ROLE 'valet_parking';