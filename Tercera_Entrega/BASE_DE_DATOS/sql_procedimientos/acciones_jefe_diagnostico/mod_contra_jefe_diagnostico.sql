DELIMITER $$

CREATE PROCEDURE mod_contra_jefe_diagnostico(
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

        DECLARE EXIT HANDLER FOR SQLEXCEPTION
        BEGIN
            -- En caso de error, anula la transaccion y envia un mensaje de error
            ROLLBACK;
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error al cambiar la clave';
        END;

        -- Inicia una transaccion
        START TRANSACTION;

        -- Cambia la contraseña del usuario jefe_diagnostico
        SET @query = CONCAT('ALTER USER ''', nombre_usuario, '''@''', nombre_host, ''' IDENTIFIED BY ''', nueva_contrasena, '''');
        PREPARE stmt FROM @query;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;

        -- Confirma la transacción
        COMMIT;

        -- Mensaje de modificacion de contraseña exitosa
        SELECT CONCAT('Cambio de clave al usuario ', nombre_actual, '@', host_actual, ' realizado exitosamente.') AS resultado;
    ELSE
        -- Si el rol actual no es autorizado, muestra un mensaje de error
        SELECT 'No tiene permisos para cambiar la clave de un usuario "jefe_diagnostico".' AS resultado;
    END IF;
END $$

DELIMITER ;