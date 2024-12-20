<?php

namespace Sigae\Models;
use function Sigae\Config\conectarDB;
use Exception;
use PDO;

abstract class Servicio{
    protected ?PDO $conn =null;
    protected $id;
    protected $precio;
    protected $fecha_inicio;
    protected $fecha_final;
    protected EstadoServicio $estado;

    public function __construct($id, $precio, $fecha_inicio, $fecha_final){
        $this->id = $id;
        $this->precio = $precio;
        $this->fecha_inicio = $fecha_inicio;
        $this->fecha_final = $fecha_final;
        $this->estado = EstadoServicio::Pendiente;
    }

    public function getId(){
        return $this->id;
    }

    public function setId($id){
        $this->id = $id;

        return $this;
    }

    public function getPrecio(){
        return $this->precio;
    }

    public function setPrecio($precio){
        $this->precio = $precio;

        return $this;
    }

    public function getFecha_inicio(){
        return $this->fecha_inicio;
    }

    public function setFecha_inicio($fecha_inicio){
        $this->fecha_inicio = $fecha_inicio;

        return $this;
    }

    public function getFecha_final(){
        return $this->fecha_final;
    }

    public function setFecha_final($fecha_final){
        $this->fecha_final = $fecha_final;

        return $this;
    }

    public function getEstado(): string{
        return $this->estado->value;
    }

    public function setEstado(EstadoServicio $estado){
        $this->estado = $estado;
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

    public function comenzarTransaccion() {
        if ($this->conn) {
            $this->conn->beginTransaction();
        }
    }

    // Método para confirmar una transacción
    public function confirmarTransaccion() {
        if ($this->conn) {
            $this->conn->commit();
        }
    }

    // Método para revertir una transacción
    public function deshacerTransaccion() {
        if ($this->conn) {
            $this->conn->rollback();
        }
    }

    public function cerrarDBConnection(){
        $this->conn = null;
    }

    abstract public function reservar($matricula);

    abstract public function actualizar();

    public static function cancelar($rol, $id){
        $conn = conectarDB($rol);
        if($conn === false){
            throw new Exception("No se puede conectar con la base de datos.");
        }

        try{
            $stmt = $conn->prepare('UPDATE servicio 
                                    SET estado = "cancelado" 
                                    WHERE id = :id');
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return true;

        } catch(Exception $e){
            error_log("Error al cancelar el servicio: ".$e->getMessage());
            throw $e;
            return false;
        }
    }

    public static function existeId($rol, $id){
        $conn = conectarDB($rol);
        if($conn === false){
            throw new Exception("No se puede conectar con la base de datos.");
        }

        try{
            $stmt = $conn->prepare('SELECT COUNT(*) FROM servicio WHERE id = :id');
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $count = $stmt->fetchColumn();

            return $count != 0;

        } catch(Exception $e){
            throw $e;
        }
    }

    public static function obtenerEstadoActual($rol, $id){
        $conn = conectarDB($rol);
        if($conn === false){
            throw new Exception("No se puede conectar con la base de datos.");
        }

        try{
            $stmt = $conn->prepare('SELECT estado FROM servicio WHERE id = :id');
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $estado = $stmt->fetchColumn();

            return $estado;

        } catch(Exception $e){
            throw $e;
        }
    }

    public static function obtenerFechaInicio($rol, $id){
        $conn = conectarDB($rol);
        if($conn === false){
            throw new Exception("No se puede conectar con la base de datos.");
        }

        try{
            $stmt = $conn->prepare('SELECT fecha_inicio FROM servicio WHERE id = :id');
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $inicio = $stmt->fetchColumn();

            return $inicio;

        } catch(Exception $e){
            throw $e;
        }
    }

    public static function obtenerPrecio($rol, $id){
        $conn = conectarDB($rol);
        if($conn === false){
            throw new Exception("No se puede conectar con la base de datos.");
        }

        try{
            $stmt = $conn->prepare('SELECT precio FROM servicio WHERE id = :id');
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $precio = $stmt->fetchColumn();

            return $precio;

        } catch(Exception $e){
            throw $e;
        }
    }

    abstract public function getServicios();
}