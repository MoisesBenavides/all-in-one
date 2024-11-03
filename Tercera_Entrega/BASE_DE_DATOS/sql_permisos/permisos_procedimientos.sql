-- Admin
GRANT EXECUTE ON PROCEDURE aio_db.alta_gerente TO 'admin_rol';
GRANT EXECUTE ON PROCEDURE aio_db.modificacion_gerente TO 'admin_rol';
GRANT EXECUTE ON PROCEDURE aio_db.baja_gerente TO 'admin_rol';

GRANT EXECUTE ON PROCEDURE aio_db.alta_jefe_diagnostico TO 'admin_rol';
GRANT EXECUTE ON PROCEDURE aio_db.modificacion_jefe_diagnostico TO 'admin_rol';
GRANT EXECUTE ON PROCEDURE aio_db.baja_jefe_diagnostico TO 'admin_rol';

GRANT EXECUTE ON PROCEDURE aio_db.alta_jefe_taller TO 'admin_rol';
GRANT EXECUTE ON PROCEDURE aio_db.modificacion_jefe_taller TO 'admin_rol';
GRANT EXECUTE ON PROCEDURE aio_db.baja_jefe_taller TO 'admin_rol';

GRANT EXECUTE ON PROCEDURE aio_db.alta_cajero TO 'admin_rol';
GRANT EXECUTE ON PROCEDURE aio_db.modificacion_cajero TO 'admin_rol';
GRANT EXECUTE ON PROCEDURE aio_db.baja_cajero TO 'admin_rol';

GRANT EXECUTE ON PROCEDURE aio_db.alta_ejecutivo TO 'admin_rol';
GRANT EXECUTE ON PROCEDURE aio_db.modificacion_ejecutivo TO 'admin_rol';
GRANT EXECUTE ON PROCEDURE aio_db.baja_ejecutivo TO 'admin_rol';

GRANT EXECUTE ON PROCEDURE aio_db.alta_valet_parking TO 'admin_rol';
GRANT EXECUTE ON PROCEDURE aio_db.modificacion_valet_parking TO 'admin_rol';
GRANT EXECUTE ON PROCEDURE aio_db.baja_valet_parking TO 'admin_rol';

-- Gerente
GRANT EXECUTE ON PROCEDURE aio_db.alta_jefe_diagnostico TO 'gerente';
GRANT EXECUTE ON PROCEDURE aio_db.modificacion_jefe_diagnostico TO 'gerente';
GRANT EXECUTE ON PROCEDURE aio_db.baja_jefe_diagnostico TO 'gerente';

GRANT EXECUTE ON PROCEDURE aio_db.alta_jefe_taller TO 'gerente';
GRANT EXECUTE ON PROCEDURE aio_db.modificacion_jefe_taller TO 'gerente';
GRANT EXECUTE ON PROCEDURE aio_db.baja_jefe_taller TO 'gerente';

GRANT EXECUTE ON PROCEDURE aio_db.alta_cajero TO 'gerente';
GRANT EXECUTE ON PROCEDURE aio_db.modificacion_cajero TO 'gerente';
GRANT EXECUTE ON PROCEDURE aio_db.baja_cajero TO 'gerente';

GRANT EXECUTE ON PROCEDURE aio_db.alta_valet_parking TO 'gerente';
GRANT EXECUTE ON PROCEDURE aio_db.modificacion_valet_parking TO 'gerente';
GRANT EXECUTE ON PROCEDURE aio_db.baja_valet_parking TO 'gerente';

-- Jefe de Taller
GRANT EXECUTE ON PROCEDURE aio_db.alta_ejecutivo TO 'jefe_taller';
GRANT EXECUTE ON PROCEDURE aio_db.modificacion_ejecutivo TO 'jefe_taller';
GRANT EXECUTE ON PROCEDURE aio_db.baja_ejecutivo TO 'jefe_taller';