<?php

require_once '../../config/db_conn.php';

class Cliente{
    private $id;
    private $ci;
    private $email;
    private $hash_contrasena;
    private $nombre;
    private $apellido;
    private $telefono;

    public function getId(){
        return $this->id;
    }

    public function setId($id){
        $this->id = $id;

        return $this;
    }

    public function getCi(){
        return $this->ci;
    }

    public function setCi($ci){
        $this->ci = $ci;

        return $this;
    }

    public function getEmail(){
        return $this->email;
    }

    public function setEmail($email){
        $this->email = $email;

        return $this;
    }

    public function getHash_contrasena(){
        return $this->hash_contrasena;
    }

    public function setHash_contrasena($hash_contrasena){
        $this->hash_contrasena = $hash_contrasena;

        return $this;
    }

    public function getNombre(){
        return $this->nombre;
    }

    public function setNombre($nombre){
        $this->nombre = $nombre;

        return $this;
    }

    public function getApellido(){
        return $this->apellido;
    }

    public function setApellido($apellido){
        $this->apellido = $apellido;

        return $this;
    }

    public function getTelefono(){
        return $this->telefono;
    }

    public function setTelefono($telefono){
        $this->telefono = $telefono;

        return $this;
    }

    public function iniciarCliente($email, $contrasena){
        try{
            $conn = conectarDB("def_cliente", "password_cliente", "localhost");

            if ($conn === false) {
                throw new Exception("No se pudo conectar a la base de datos.");
            }

            $stmt = $conn->prepare('SELECT * FROM cliente WHERE email = :email');

            $stmt->bindParam(':email', $email);
            
            $stmt->execute();

            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

            if($cliente && password_verify($contrasena, $cliente['hash_contrasena'])){
                $this->setId($cliente['id']);
                $this->setCi($cliente['ci']);
                $this->setEmail($cliente['email']);
                $this->setHash_contrasena($cliente['hash_contrasena']);
                $this->setNombre($cliente['nombre']);
                $this->setApellido($cliente['apellido']);
                $this->setTelefono($cliente['telefono']);
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

    public function guardarCliente($ci, $email, $contrasena, $nombre, $apellido, $telefono) {
        // Encriptar contraseña (bcrypt)
        $hash_contrasena = password_hash($contrasena, PASSWORD_BCRYPT);
        try {
            $conn = conectarDB("def_cliente", "password_cliente", "localhost");
            
            if ($conn === false) {
                throw new Exception("No se pudo conectar a la base de datos.");
            }
    
            $stmt = $conn->prepare('INSERT INTO cliente (ci, email, hash_contrasena, nombre, apellido, telefono) 
                                    VALUES (:ci, :email, :hash_con, :nom, :ape, :tel)');
            
            $stmt->bindParam(':ci', $ci);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':hash_con', $hash_contrasena);
            $stmt->bindParam(':nom', $nombre);
            $stmt->bindParam(':ape', $apellido);
            $stmt->bindParam(':tel', $telefono);
                
            $stmt->execute();
            return true; // Agregado exitosamente;

        } catch (Exception $e) {
            error_log($e->getMessage());
            error_log("Error al registrarse: " . $e->getMessage());
            return false;
            
        } finally {
            $conn = null;
        }
    }

    public function cargarMisVehiculos($id){
        try {
            $conn = conectarDB("def_cliente", "password_cliente", "localhost");
            $stmt = $conn->prepare('SELECT * FROM vehiculo v 
                                    WHERE v.matricula IN (SELECT t.matricula 
                                    FROM tiene t 
                                    WHERE t.id_cliente=:id)');

            $stmt->bindParam(':id', $id);

            $stmt->execute();
            
            $misVehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log(print_r($misVehiculos, true));

            return $misVehiculos;

        } catch (Exception $e) {
            error_log($e->getMessage()); // Registro del error en el log
            return false; // False si hubo un error de base de datos
        } finally {
            $conn = null;
        }
    }

    public static function existeEmail($email) {
        try {
            $conn = conectarDB("def_cliente", "password_cliente", "localhost");
            $stmt = $conn->prepare('SELECT COUNT(*) FROM cliente WHERE email = :email');
            $stmt->bindParam(':email', $email);

            $stmt->execute();
            
            $count = $stmt->fetchColumn();

            return $count != 0;

        } catch (Exception $e) {
            error_log($e->getMessage()); // Registro del error en el log
            return false; // False si hubo un error de base de datos
        } finally {
            $conn = null;
        }
    }

    public function getClientes(){
    }
}
