<?php

namespace Sigae\Config;
use Exception;
use PDO;
use PDOException;

function conectarDB($rol){

    try {
        // Cargar json con credenciales
        $pathCredenciales='/var/www/private/credencialesBD.json';

        if(!file_exists($pathCredenciales)){
            throw new Exception("Archivo de credenciales no encontrado.");
        }

        $contenidoJson = file_get_contents($pathCredenciales);

        $credenciales = json_decode($contenidoJson, true);

        // Verificar si hubo error en la decodificación del JSON
        if ($credenciales === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error al decodificar el JSON: ' . json_last_error_msg());
        }

        // Obtener credenciales por rol
        $credencialesRol = [
            'user' => $credenciales[$rol]['user'],
            'pswd' => $credenciales[$rol]['password'],
            'host' => $credenciales[$rol]['hostname'],
        ];
        
        if (!$credencialesRol){
            throw new Exception("Credenciales de conexión no encontradas.");
        }

        // Creacion de conexión a la base de datos
        $conexionBD = new PDO("mysql:host=" . $credencialesRol['host'] . ":3306;dbname=aio_db", 
            $credencialesRol['user'], 
            $credencialesRol['pswd']
        );
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
