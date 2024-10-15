<?php

namespace Sigae\Models;
use function Sigae\Config\conectarDB;

class Vehiculo
{
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

    public function registrarYa($id_cliente){
        $matricula = $this->getMatricula();
        $tipo = $this->getTipo();
        try {
            $conn = conectarDB("def_cliente", "password_cliente", "localhost");
            
            if ($conn === false) {
                throw new Exception("No se pudo conectar a la base de datos.");
            }
    
            $stmt = $conn->prepare('INSERT INTO vehiculo (matricula, tipo) VALUES (:mat, :tipo)');
            
            $stmt->bindParam(':mat', $matricula);
            $stmt->bindParam(':tipo', $tipo);
                
            $stmt->execute();
            
            return $this->vincularCliente($id_cliente) !== false; // Registrar vínculo con cliente;

        } catch (Exception $e) {
            error_log($e->getMessage());
            error_log("Error al registrar el vehículo: " . $e->getMessage());
            return false;
            
        } finally {
            $conn = null;
        }
        

    }

    public function vincularCliente($id_cliente){
        $matricula = $this->getMatricula();
        try {
            $conn = conectarDB("def_cliente", "password_cliente", "localhost");
            
            if ($conn === false) {
                throw new Exception("No se pudo conectar a la base de datos.");
            }
    
            $stmt = $conn->prepare('INSERT INTO tiene (id_cliente, matricula) VALUES (:id_cl, :mat)');
            
            $stmt->bindParam(':id_cl', $id_cliente);
            $stmt->bindParam(':mat', $matricula);
                
            $stmt->execute();
            

        } catch (Exception $e) {
            error_log($e->getMessage());
            error_log("Error al registrar el vehículo: " . $e->getMessage());
            
        } finally {
            $conn = null;
        }
    }

    public static function existeMatricula($matricula) {
        try {
            $conn = conectarDB("def_cliente", "password_cliente", "localhost");
            $stmt = $conn->prepare('SELECT COUNT(*) FROM vehiculo WHERE matricula = :mat');
            $stmt->bindParam(':mat', $matricula);
            $stmt->execute();
            
            $count = $stmt->fetchColumn();

            return $count != 0;

        } catch (Exception $e) {
            error_log($e->getMessage()); // Registro del error en el log
            return false; // Devuelve false si hubo un error de base de datos
        } finally {
            $conn = null;
        }
    }
    
    public function getVehiculos(){
    }

}