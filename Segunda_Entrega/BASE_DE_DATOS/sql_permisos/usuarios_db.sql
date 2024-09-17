-- CREACION DE USUARIOS
-- admin
CREATE USER 'admin'@'localhost' IDENTIFIED BY '1234';

-- cliente predeterminado
CREATE USER 'def_cliente'@'localhost' IDENTIFIED BY '';

-- gerente predeterminado
CREATE USER 'def_gerente'@'localhost' IDENTIFIED BY '';

-- ejecutivo predeterminado
CREATE USER 'def_ejecutivo'@'localhost' IDENTIFIED BY '';

-- cajero predeterminado
CREATE USER 'def_cajero'@'localhost' IDENTIFIED BY '';

-- jefe de diagnostico predeterminado
CREATE USER 'def_jefe_diagnostico'@'localhost' IDENTIFIED BY '';

-- jefe de taller predeterminado
CREATE USER 'def_jefe_taller'@'localhost' IDENTIFIED BY '';

-- valet parking predeterminado
CREATE USER 'def_valet_parking'@'localhost' IDENTIFIED BY '';

-------------------------------------------------------------------

-- ASIGNAR ROLES A USUARIOS
-- admin
GRANT 'admin_rol' TO 'admin'@'localhost';

-- cliente predeterminado
GRANT 'cliente' TO 'def_cliente'@'localhost';

-- gerente predeterminado
GRANT 'gerente' TO 'def_gerente'@'localhost';

-- ejecutivo predeterminado
GRANT 'ejecutivo' TO 'def_ejecutivo'@'localhost';

-- cajero predeterminado
GRANT 'cajero' TO 'def_cajero'@'localhost';

-- jefe de diagnostico predeterminado
GRANT 'jefe_diagnostico' TO 'def_jefe_diagnostico'@'localhost';

-- jefe de taller predeterminado
GRANT 'jefe_taller' TO 'def_jefe_taller'@'localhost';

-- valet parking predeterminado
GRANT 'valet_parking' TO 'def_valet_parking'@'localhost';