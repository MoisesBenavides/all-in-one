DELIMITER $$

CREATE PROCEDURE alta_valet_parking(
    IN nombre_usuario VARCHAR(50),
    IN nombre_host VARCHAR(50),
    IN nueva_contrasena VARCHAR(60)
)
BEGIN
   -- Construye la consulta de creacion de usuario con rol de valet_parking
    SET @query = CONCAT('CREATE USER "', nombre_usuario, '"@"', nombre_host, '" IDENTIFIED BY "', nueva_contrasena, '" DEFAULT ROLE "valet_parking";');

    PREPARE stmt FROM @query;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Mensaje de alta exitosa
    SELECT CONCAT('Usuario ', nombre_usuario, '@', nombre_host, ' creado exitosamente.') AS resultado;
END $$

DELIMITER ;
