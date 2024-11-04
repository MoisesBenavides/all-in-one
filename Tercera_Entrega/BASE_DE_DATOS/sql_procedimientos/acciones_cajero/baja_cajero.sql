DELIMITER $$

CREATE PROCEDURE baja_cajero(
    IN nombre_usuario VARCHAR(50),
    IN nombre_host VARCHAR(50)
)
BEGIN
    -- Construye la consulta de eliminacion de usuario
    SET @query = CONCAT('DROP USER "', nombre_usuario, '"@"', nombre_host, '";');

    PREPARE stmt FROM @query;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Mensaje de baja exitosa
    SELECT CONCAT('Usuario ', nombre_usuario, '@', nombre_host, ' eliminado exitosamente.') AS resultado;
END $$

DELIMITER ;
