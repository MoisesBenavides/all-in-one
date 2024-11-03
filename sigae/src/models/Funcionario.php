<?php

namespace Sigae\Models;
use function Sigae\Config\conectarDB;
use PDO;
use PDOException;
use Exception;

class Funcionario{
    private ?PDO $conn =null;
    private $usuario;
    private $host;
    private $rol;

    public function __construct($usuario, $host, $rol){
        $this->usuario = $usuario;
        $this->host = $host;
        $this->rol = $rol;
    }

    public function setDBConnection($rol){
        $this->conn = conectarDB($rol);
        if($this->conn === false){
            throw new Exception("No se puede conectar con la base de datos.");
        }
        return $this;
    }

    public function getDBConnection(){
        return $this->conn;
    }

    public function getUsuario(){
        return $this->usuario;
    }

    public function setUsuario($usuario){
        $this->usuario = $usuario;

        return $this;
    }

    public function getHost(){
        return $this->host;
    }

    public function setHost($host){
        $this->host = $host;

        return $this;
    }

    public function getRol(){
        return $this->rol;
    }

    public function setRol($rol){
        $this->rol = $rol;

        return $this;
    }

    public function verificarCredenciales($contrasena) {
        try {
            $usuario = $this->getUsuario();
            $host = $this->getHost();

            // Conectar sin especificar base de datos
            $dsn = 'mysql:host='. $host;

            $pdo = new PDO($dsn, $usuario, $contrasena, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            return true;
    
        } catch (Exception $e) {
            // Debug error
            error_log($e->getMessage());
            return false;
        } finally {
            // Desconectar de la base de datos
            $pdo = null;
        }
    }

    public function iniciarFuncionario($usuario){
        try {
            $conn = conectarDB("app_user");
            $conn->exec("SET ROLE 'verificador_credenciales'");
            $stmt = $conn->prepare('SELECT from_user AS rol FROM mysql.role_edges WHERE to_user = :usuario');
            $stmt->bindParam(':usuario', $usuario);
            $stmt->execute();
            
            // Obtiene un solo resultado
            $rolData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$rolData) {
                throw new PDOException("No se encontró un rol asignado a este usuario.");
            }
    
            // Setea el usuario y el rol si se encuentra
            $this->setUsuario($usuario);
            $this->setRol($rolData['rol']);
    
            return true;
    
        } catch (PDOException $e) {
            // Debug error
            throw $e;
        } finally {
            $conn = null; // Cierra la conexión
        }
    }

    public static function getFuncionariosPorRol($rol_loggeado, $rol_a_buscar){
        try{
            $conn = conectarDB($rol_loggeado);
            $stmt = $conn->prepare('SELECT to_user AS usuario, to_host AS host
                                    FROM mysql.role_edges
                                    WHERE from_user = :rolBuscado
                                    ORDER BY to_user;');

            $stmt->bindParam(':rolBuscado', $rol_a_buscar);

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch(PDOException $e){
            //Debug
            error_log("Error al cargar funcionarios: ".$e);
            throw $e;
        } finally {
            $conn = null; // Cierra la conexión
        }
    }
}