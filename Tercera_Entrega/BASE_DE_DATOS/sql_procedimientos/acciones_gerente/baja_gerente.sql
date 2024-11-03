DELIMITER $$

CREATE PROCEDURE baja_gerente(
    IN nombre_usuario VARCHAR(50),
    IN nombre_host VARCHAR(50)
)
BEGIN
    -- Obtiene el rol del usuario que ejecuta el procedimento
    DECLARE rol_actual VARCHAR(50);
    SELECT CURRENT_ROLE() INTO rol_actual;

    -- Vefirica si el rol actual es autorizado
    IF rol_actual = 'admin_rol' THEN
        -- Construye la consulta de eliminacion de usuario
        SET @query = CONCAT('DROP USER "', nombre_usuario, '"@"', nombre_host, '";');

        PREPARE stmt FROM @query;

        EXECUTE stmt;

        DEALLOCATE PREPARE stmt;

        -- Mensaje de baja exitosa
        SELECT CONCAT('Usuario ', nombre_usuario, '@', nombre_host, ' eliminado exitosamente.') AS resultado;
    ELSE
        -- Si el rol actual no es autorizado, muestra un mensaje de error
        SELECT 'No tiene permisos para dar de baja un usuario "gerente".' AS resultado;
    END IF;
END $$

DELIMITER ;
