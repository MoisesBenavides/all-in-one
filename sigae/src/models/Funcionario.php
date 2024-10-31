<?php

namespace Sigae\Models;
use function Sigae\Config\conectarDB;
use PDO;
use PDOException;
use Exception;

class Funcionario{
    private ?PDO $conn =null;
    private $usuario;
    private $rol;

    public function __construct($usuario, $rol){
        $this->usuario = $usuario;
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

    public function getRol(){
        return $this->rol;
    }

    public function setRol($rol){
        $this->rol = $rol;

        return $this;
    }

    public function verificarCredenciales($contrasena) {
        try {
            // Conectar sin especificar base de datos
            $dsn = 'mysql:host=localhost';
            $usuario = $this->getUsuario();
            $pdo = new PDO($dsn, $usuario, $contrasena, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            return true;
    
        } catch (PDOException $e) {
            $codigoError = $e->getCode();
    
            // Manejamos los códigos específicos pero lanzamos un error PDOException
            if ($codigoError === '1045') {
                throw new PDOException("Error: Usuario encontrado, pero la contraseña es incorrecta.", 1045, $e);
            } elseif ($codigoError === '1044') {
                throw new PDOException("Error: Usuario encontrado, pero no tiene permisos para acceder a esta base de datos.", 1044, $e);
            } else {
                // Re-lanzar la excepción original para otros errores
                throw $e;
            }
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
}