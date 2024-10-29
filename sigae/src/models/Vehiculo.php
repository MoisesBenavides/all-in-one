<?php

namespace Sigae\Models;
use function Sigae\Config\conectarDB;
use PDO;
use Exception;


class Vehiculo {
    private ?PDO $conn =null;
    private $matricula;
    private $marca;
    private $modelo;
    private $tipo;
    private $color;

    public function __construct($matricula, $marca, $modelo, $tipo, $color){
        $this->matricula = $matricula;
        $this->marca = $marca;
        $this->modelo = $modelo;
        $this->tipo = $tipo;
        $this->color = $color;
    }

    public function setDBConnection($user, $password , $hostname){
        $this->conn = conectarDB($user, $password, $hostname);
        if($this->conn === false){
            throw new Exception("No se puede conectar con la base de datos.");
        }
        return $this;
    }

    public function getDBConnection(){
        return $this->conn;
    }

    public function getMatricula()
    {
        return $this->matricula;
    }

    public function setMatricula($matricula)
    {
        $this->matricula = $matricula;
    }

    public function getMarca()
    {
        return $this->marca;
    }

    public function setMarca($marca)
    {
        $this->marca = $marca;
    }

    public function getModelo()
    {
        return $this->modelo;
    }

    public function setModelo($modelo)
    {
        $this->modelo = $modelo;
    }

    public function getTipo()
    {
        return $this->tipo;
    }

    public function setTipo($tipo)
    {
        $this->tipo = $tipo;
    }

    public function getColor()
    {
        return $this->color;
    }

    public function setColor($color)
    {
        $this->color = $color;
    }

    public function comenzarTransaccion() {
        if ($this->conn) {
            $this->conn->beginTransaction();
        }
    }

    public function confirmarTransaccion() {
        if ($this->conn) {
            $this->conn->commit();
        }
    }

    public function deshacerTransaccion() {
        if ($this->conn) {
            $this->conn->rollback();
        }
    }

    public function cerrarDBConnection(){
        $this->conn = null;
    }

    public function create(){
        $matricula = $this->getMatricula();
        $marca = $this->getMarca();
        $modelo = $this->getModelo();
        $tipo = $this->getTipo();
        $color = $this->getColor();

        try {
            $stmt = $this->conn->prepare('INSERT INTO vehiculo (matricula, marca, modelo, tipo, color) VALUES (:mat, :marca, :mod, :tipo, :color)');
            
            $stmt->bindParam(':mat', $matricula);
            $stmt->bindParam(':marca', $marca);
            $stmt->bindParam(':mod', $modelo);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':color', $color);
                
            $stmt->execute();
            
            return true; // Registro de vehículo exitoso

        } catch (Exception $e) {
            error_log("Error al registrar el vehículo: " . $e->getMessage());
            return false;
        }
    }

    public function unlink(){
        $matricula = $this->getMatricula();
        try {
            $stmt = $this->conn->prepare('DELETE FROM tiene WHERE matricula = :mat');
            
            $stmt->bindParam(':mat', $matricula);
                
            $stmt->execute();
            
            return true; // Desvínculo de vehiculo con cliente exitoso

        } catch (Exception $e) {
            error_log("Error al desvincular el vehículo: " . $e->getMessage());
            return false;
        }
    }

    public function edit(){
        $matricula = $this->getMatricula();
        $marca = $this->getMarca();
        $modelo = $this->getModelo();
        $tipo = $this->getTipo();
        $color = $this->getColor();

        try {
            $stmt = $this->conn->prepare('UPDATE vehiculo SET marca = :marca, modelo = :mod, tipo = :tipo, color = :color WHERE matricula = :mat');
            
            $stmt->bindParam(':mat', $matricula);
            $stmt->bindParam(':marca', $marca);
            $stmt->bindParam(':mod', $modelo);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':color', $color);
                
            $stmt->execute();
            
            return true; // Registro de vehículo exitoso

        } catch (Exception $e) {
            error_log("Error al modificar el vehículo: " . $e->getMessage());
            return false;
        }
    }

    public function registrarYa(){
        $matricula = $this->getMatricula();
        $tipo = $this->getTipo();
        try {
            $stmt = $this->conn->prepare('INSERT INTO vehiculo (matricula, tipo) VALUES (:mat, :tipo)');
            
            $stmt->bindParam(':mat', $matricula);
            $stmt->bindParam(':tipo', $tipo);
                
            $stmt->execute();

            return true; // Registro de vehículo exitoso

        } catch (Exception $e) {
            error_log("Error al registrar el vehículo: " . $e->getMessage());
            return false;
        }
    }

    public function vincularCliente($id_cliente){
        $matricula = $this->getMatricula();
        try {
            $stmt = $this->conn->prepare('INSERT INTO tiene (id_cliente, matricula) VALUES (:id_cl, :mat)');
            
            $stmt->bindParam(':id_cl', $id_cliente);
            $stmt->bindParam(':mat', $matricula);
                
            $stmt->execute();

            return true; // Vinculación con cliente exitosa
        } catch (Exception $e) {
            error_log("Error al registrar el vehículo: " . $e->getMessage());
            return false;
            
        }
    }

    public function existeMatricula($matricula) {
        try {
            $stmt = $this->conn->prepare('SELECT COUNT(*) FROM vehiculo WHERE matricula = :mat');
            $stmt->bindParam(':mat', $matricula);
            $stmt->execute();
            
            $count = $stmt->fetchColumn();

            return $count != 0;

        } catch (Exception $e) {
            error_log($e->getMessage()); // Registro del error en el log
            return false; // Devuelve false si hubo un error de base de datos
        }
    }

    public function tieneServiciosVinculados($matricula) {
        try {
            $stmt = $this->conn->prepare('SELECT COUNT(*) FROM servicio WHERE matricula = :mat');
            $stmt->bindParam(':mat', $matricula);
            $stmt->execute();
            
            $count = $stmt->fetchColumn();

            return $count != 0;

        } catch (Exception $e) {
            error_log($e->getMessage()); // Registro del error en el log
            return false; // Devuelve false si hubo un error de base de datos
        }
    }

    public function obtenerServiciosPendientesVinculados($matricula) {
        try {
            $stmt = $this->conn->prepare('SELECT * FROM servicio WHERE matricula = :mat AND estado = "pendiente"');
            $stmt->bindParam(':mat', $matricula);
            $stmt->execute();
            
            $serviciosPend = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $serviciosPend;

        } catch (Exception $e) {
            error_log($e->getMessage()); // Registro del error en el log
            return false; // Devuelve false si hubo un error de base de datos
        }
    }

    public static function cargarMisVehiculos($id){
        try {
            $conn = conectarDB("def_cliente", "password_cliente", "localhost");
            $stmt = $conn->prepare('SELECT * FROM vehiculo v 
                                    WHERE v.matricula IN (SELECT t.matricula 
                                    FROM tiene t 
                                    WHERE t.id_cliente=:id)');

            $stmt->bindParam(':id', $id);

            $stmt->execute();
            
            $misVehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $misVehiculos;

        } catch (Exception $e) {
            error_log($e->getMessage()); // Registro del error en el log
            return false; // False si hubo un error de base de datos
        } finally {
            $conn = null;
        }
    }
    
    public function getVehiculos(){
    }

}