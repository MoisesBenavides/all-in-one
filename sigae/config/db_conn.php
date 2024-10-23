<?php

namespace Sigae\Config;
use Exception;
use PDO;
use PDOException;

function conectarDB($user, $pswd, $hostname){

    try {
        // Creacion de conexión a la base de datos
        $conexionBD = new PDO("mysql:host=$hostname:3306;dbname=aio_db", $user, $pswd);
        $conexionBD->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
        return $conexionBD;
    } catch(PDOException $e) {
        // Retornar un mensaje de error, lanza una excepción
        error_log("Error de conexión: " . $e->getMessage());
        throw new Exception("Fallo en la conexión a la base de datos: " . $e->getMessage());
    }
    exit();
}

?>
