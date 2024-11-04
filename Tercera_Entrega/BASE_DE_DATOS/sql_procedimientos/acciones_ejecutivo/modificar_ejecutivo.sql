DELIMITER $$

CREATE PROCEDURE modificar_ejecutivo(
    IN nombre_actual VARCHAR(50),
    IN host_actual VARCHAR(50),
    IN nuevo_nombre VARCHAR(50),
    IN nuevo_host VARCHAR(50)
)
BEGIN
    -- Construye la consulta de modificacion de usuario con rol de ejecutivo
    SET @query = CONCAT('RENAME USER "', nombre_actual, '"@"', host_actual, '" TO "', nuevo_nombre, '"@"', nuevo_host, '";');

    PREPARE stmt FROM @query;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Mensaje de modificacion exitosa
    SELECT CONCAT('Usuario ', nombre_actual, '@', host_actual, ' modificado a ', nuevo_nombre, '@', nuevo_host, ' exitosamente.') AS resultado;
END $$

DELIMITER ;
