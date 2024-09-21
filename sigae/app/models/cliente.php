<?php

require_once '../app/db_config/db_conn.php';

class Cliente{
    private $id;
    private $ci;
    private $email;
    private $hash_contrasena;
    private $nombre;
    private $apellido;
    private $telefono;

    public function iniciarCliente($email, $contrasena){
        try{
            $conn = conectarDB("def_cliente", "password_cliente", "localhost");

            if ($conn === false) {
                throw new Exception("No se pudo conectar a la base de datos.");
            }

            $stmt = $conn->prepare('SELECT hash_contrasena FROM cliente WHERE email = :email');

            $stmt->bindParam(':email', $email);
            
            $stmt->execute();

            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($cliente && password_verify($contrasena, $cliente['hash_contrasena'])) {
                return true;
            } else {
                echo "Correo o contraseña incorrectos.";
                return false;
            }

        } catch(Exception $e){
            error_log($e->getMessage());
            echo "Error al iniciar sesión: " . $e->getMessage();
            return false;
        } finally {
            $conn = null;
        }
    }

    public function agregarCliente($email, $contrasena, $nombre, $apellido) {
        // Encriptar contraseña (bcrypt)
        $hash_contrasena = password_hash($contrasena, PASSWORD_BCRYPT);
        try {
            $conn = conectarDB("def_cliente", "password_cliente", "localhost");
            
            if ($conn === false) {
                throw new Exception("No se pudo conectar a la base de datos.");
            }
    
            if (!$this->existeEmail($email)) {
                // Consulta con parámetros
                $stmt = $conn->prepare('INSERT INTO cliente (email, hash_contrasena, nombre, apellido) VALUES (:email, :hash_con, :nom, :ape)');
            
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':hash_con', $hash_contrasena);
                $stmt->bindParam(':nom', $nombre);
                $stmt->bindParam(':ape', $apellido);
                
                $stmt->execute();
                return true; // Agregado exitosamente
            } else {
                echo "Ya existe un usuario con el correo ingresado.";
                return false; // Correo ya existe
            }

        } catch (Exception $e) {
            error_log($e->getMessage());
            echo "Error al registrarse: " . $e->getMessage();
            return false;
        } finally {
            $conn = null;
        }
    }

    function existeEmail($email){
        try{
            $conn = conectarDB("def_cliente", "password_cliente", "localhost");

            $stmt = $conn->prepare('SELECT COUNT(*) FROM cliente WHERE email = :email');
        
            $stmt->bindParam(':email', $email);
            
            $stmt->execute();
    
            $count = $stmt->fetchColumn();

            return $count != 0 ? true : false;

        } catch (Exception $e){
            echo $e->getMessage();

        } finally {
            $conn = null;
        }
    }

}