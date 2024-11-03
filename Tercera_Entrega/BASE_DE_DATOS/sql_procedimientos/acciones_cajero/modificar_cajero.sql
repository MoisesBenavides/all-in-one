DELIMITER $$

CREATE PROCEDURE modificar_cajero(
    IN nombre_actual VARCHAR(50),
    IN host_actual VARCHAR(50),
    IN nuevo_nombre VARCHAR(50),
    IN nuevo_host VARCHAR(50)
)
BEGIN
    -- Obtiene el rol del usuario que ejecuta el procedimento
    DECLARE rol_actual VARCHAR(50);
    SELECT CURRENT_ROLE() INTO rol_actual;

    -- Vefirica si el rol actual es autorizado
    IF rol_actual = 'gerente' OR 'admin_rol' THEN
        -- Construye la consulta de modificacion de usuario con rol de cajero
        SET @query = CONCAT('RENAME USER "', nombre_actual, '"@"', host_actual, '" TO "', nuevo_nombre, '"@"', nuevo_host, '";');

        PREPARE stmt FROM @query;

        EXECUTE stmt;

        DEALLOCATE PREPARE stmt;

        -- Mensaje de modificacion exitosa
        SELECT CONCAT('Usuario ', nombre_actual, '@', host_actual, ' modificado a ', nuevo_nombre, '@', nuevo_host, ' exitosamente.') AS resultado;
    ELSE
        -- Si el rol actual no es autorizado, muestra un mensaje de error
        SELECT 'No tiene permisos para modificar un usuario "cajero".' AS resultado;
    END IF;
END $$

DELIMITER ;
