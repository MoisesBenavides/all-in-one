DELIMITER $$

CREATE PROCEDURE alta_cajero(
    IN nombre_usuario VARCHAR(50),
    IN nombre_host VARCHAR(50),
    IN nueva_contrasena VARCHAR(60)
)
BEGIN
    -- Obtiene el rol del usuario que ejecuta el procedimento
    DECLARE rol_actual VARCHAR(50);
    SELECT CURRENT_ROLE() INTO rol_actual;

    -- Vefirica si el rol actual es autorizado
    IF rol_actual = 'gerente' OR 'admin_rol' THEN
        -- Construye la consulta de creacion de usuario con rol de cajero
        SET @query = CONCAT('CREATE USER "', nombre_usuario, '"@"', nombre_host, '" IDENTIFIED BY "', nueva_contrasena, '" DEFAULT ROLE "cajero";');

        PREPARE stmt FROM @query;

        EXECUTE stmt;

        DEALLOCATE PREPARE stmt;

        -- Mensaje de alta exitosa
        SELECT CONCAT('Usuario ', nombre_usuario, '@', nombre_host, ' creado exitosamente.') AS resultado;
    ELSE
        -- Si el rol actual no es autorizado, muestra un mensaje de error
        SELECT 'No tiene permisos para dar de alta un usuario "cajero".' AS resultado;
    END IF;
END $$

DELIMITER ;
