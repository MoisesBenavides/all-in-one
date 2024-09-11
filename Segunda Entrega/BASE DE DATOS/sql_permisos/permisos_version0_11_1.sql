-- CREACION DE ROLES
CREATE ROLE 'gerente';
CREATE ROLE 'cliente';
CREATE ROLE 'ejecutivo';
CREATE ROLE 'cajero';
CREATE ROLE 'jefe_diagnostico';
CREATE ROLE 'jefe_taller';
CREATE ROLE 'valet_parking';

-- PERMISOS
-- gerente
GRANT SELECT, INSERT, UPDATE, DELETE ON aio.* TO 'gerente';

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

-- ejecutivo
/*El ejecutivo puede ingresar la marca, modelo y color de un vehículo*/
GRANT SELECT, UPDATE ON aio.vehiculo TO 'ejecutivo';

-- SET ROLES
SET ROLE 'gerente';
SET ROLE 'cajero';
SET ROLE 'jefe_diagnostico';
SET ROLE 'jefe_taller';
SET ROLE 'ejecutivo';
SET ROLE 'valet_parking';
SET ROLE 'cliente';