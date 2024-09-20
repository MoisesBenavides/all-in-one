<?php

class Cliente{
    private $id;
    private $ci;
    private $email;
    private $hash_contrasena;
    private $nombre;
    private $apellido;
    private $telefono;

    public function agregarCliente($email, $contrasena, $nombre, $apellido) {
        $hash_contrasena = password_hash($contrasena, PASSWORD_BCRYPT);
        try {
            $conn = conectarDB("def_cliente", "password_cliente", "localhost");

            if ($this->nuevoEmail($email)) {
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
            echo "Error al agregar cliente: " . $e->getMessage();
            return false;
        } finally {
            $conn = null;
        }
    }

    function nuevoEmail($email){
        try{
            $conn = conectarDB("def_cliente", "password_cliente", "localhost");

            $stmt = $conn->prepare('SELECT COUNT(*) FROM cliente WHERE email = :email');
        
            $stmt->bindParam(':email', $email);
            
            $stmt->execute();
    
            $count = $stmt->fetchColumn();

            return $count == 0 ? true : false;

        } catch (Exception $e){
            echo $e->getMessage();

        } finally {
            $conn = null;
        }
    }

}