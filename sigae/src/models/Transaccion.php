<?php

namespace Sigae\Models;
use function Sigae\Config\conectarDB;
use PDO;
use Exception;

class Transaccion{
    private ?PDO $conn =null;
    private $id;
    private TipoTransaccion $tipo;
    private $cantidad;
    private $fecha;
    

    public function __construct($id, TipoTransaccion $tipo, $cantidad, $fecha){
        $this->id = $id;
        $this->tipo = $tipo;
        $this->cantidad = $cantidad;
        $this->fecha = $fecha;
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

    public function getId(){
        return $this->id;
    }

    public function setId($id){
        $this->id = $id;

        return $this;
    }

    public function getTipo(): string{
        return $this->tipo->value;
    }

    public function setTipo(TipoTransaccion $tipo){
        $this->tipo = $tipo;
    }

    public function getCantidad(){
        return $this->cantidad;
    }

    public function setCantidad($cantidad){
        $this->cantidad = $cantidad;

        return $this;
    }

    public function getFecha(){
        return $this->fecha;
    }

    public function setFecha($fecha){
        $this->fecha = $fecha;

        return $this;
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

    public function registrarTransaccion($idProd){
        $tipo = $this->getTipo();
        $cantidad = $this->getCantidad();
        $fecha = $this->getFecha();
    
        try {
            $stmt = $this->conn->prepare('INSERT INTO transaccion (id_producto, cantidad, tipo, fecha) 
                                    VALUES (:idProd, :cant, :tip, :fecha)');
    
            $stmt->bindParam(':idProd', $idProd);
            $stmt->bindParam(':cant', $cantidad);
            $stmt->bindParam(':tip', $tipo);
            $stmt->bindParam(':fecha', $fecha);
                
            $stmt->execute();
    
            return true;
    
        } catch (Exception $e) {
            throw $e;
        }
    }
    
}