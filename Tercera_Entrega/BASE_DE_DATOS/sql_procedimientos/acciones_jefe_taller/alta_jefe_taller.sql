DELIMITER $$

CREATE PROCEDURE alta_jefe_taller(
    IN nombre_usuario VARCHAR(50),
    IN nombre_host VARCHAR(50),
    IN nueva_contrasena VARCHAR(60)
)
BEGIN
    -- Construye la consulta de creacion de usuario con rol de jefe_taller
    SET @query = CONCAT('CREATE USER "', nombre_usuario, '"@"', nombre_host, '" IDENTIFIED BY "', nueva_contrasena, '" DEFAULT ROLE "jefe_taller";');

    PREPARE stmt FROM @query;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Mensaje de alta exitosa
    SELECT CONCAT('Usuario ', nombre_usuario, '@', nombre_host, ' creado exitosamente.') AS resultado;
END $$

DELIMITER ;
