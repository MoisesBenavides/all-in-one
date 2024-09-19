<?php

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

    public function agregarCliente($email, $contrasena, $nombre, $apellido){
        $hash_contrasena = password_hash($contrasena, PASSWORD_BCRYPT);
        try{
            $conn = conectarDB("def_cliente", "", "localhost");

            if($this->nuevoEmail($email)){
                // Consulta con parámetros
                $stmt = $conn->prepare('INSERT INTO cliente (email, hash_contrasena, nombre, apellido) VALUES (:email, :hash_con, :nom, :ape)');
            
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':hash_con', $hash_contrasena);
                $stmt->bindParam(':nom', $nombre);
                $stmt->bindParam(':ape', $apellido);
                
                $stmt->execute();
            } else{
                echo "Ya existe un usuario con el correo ingresado.";
            }

        } catch (Exception $e){
            echo $e->getMessage();

        } finally {
            $conn = null;
        }
    }

    function nuevoEmail($email){
        try{
            $conn = conectarDB("def_cliente", "", "localhost");

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