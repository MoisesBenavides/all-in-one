-- CREACION DE USUARIOS
-- cliente
CREATE USER 'def_client'@'localhost' IDENTIFIED BY '';

-- ASIGNAR ROLES A USUARIOS
-- cliente
GRANT 'cliente' TO 'def_client'@'localhost';
