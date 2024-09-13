<?php

function conectarDB($user, $pswd, $hostname){

    try {
        // Creacion de conexión a la base de datos
        $conexionBD = new PDO("mysql:host=$hostname:3306;dbname=aio_db", $user, $pswd);
        $conexionBD->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
        return $conexionBD;
    } catch(PDOException $e) {
        /* En caso de un error de conexión, registra un mensaje con el error 
        en un log y retorna el mensaje */
        error_log("Error de conexión: " . $e->getMessage());
        return "Fallo en la conexión: " . $e->getMessage();
        exit();
    }
}

?>