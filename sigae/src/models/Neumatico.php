<?php

namespace Sigae\Models;
use function Sigae\Config\conectarDB;
use PDO;
use Exception;

class Neumatico extends Producto{
    private ?PDO $conn =null;
    private $tamano;
    private $modelo;
    private $tipo;
    
        public function __construct($tamano = null, $modelo = null, $tipo = null, $id = null, $upc = null, 
                                    $precio = null, $marca = null, $fecha_creacion = null, $stock = null){
            parent::__construct($id, $upc, $precio, $marca, $fecha_creacion, $stock);
            $this->tamano = $tamano;
            $this->modelo = $modelo;
            $this->tipo = $tipo;
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

    public function getTamano(){
        return $this->tamano;
    }

    public function setTamano($tamano){
        $this->tamano = $tamano;

        return $this;
    }

    public function getModelo(){
        return $this->modelo;
    }

    public function setModelo($modelo){
        $this->modelo = $modelo;

        return $this;
    }

    public function getTipo(){
        return $this->tipo;
    }
 
    public function setTipo($tipo){
        $this->tipo = $tipo;

        return $this;
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

    public function getProductosDisp(){
        try{
            $stmt = $this->conn->prepare('SELECT p.id, p.precio, p.marca, p.fecha_creacion 
                                                n.tamano, n.modelo, n.tipo
                                        FROM producto p 
                                        JOIN neumatico n ON p.id=n.id_producto
                                        WHERE p.stock > 0');
            $stmt->execute();

            $neumaticos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $neumaticos;

        } catch(Exception $e){
            error_log("Error al cargar otros productos: ".$e->getMessage());
            throw $e;
            return;
        }
    }

    public function getProductosDetallados(){
        try{
            $stmt = $this->conn->prepare('SELECT p.id, p.upc, p.precio, 
                                                p.marca, p.fecha_creacion, p.stock 
                                                n.tamano, n.modelo, n.tipo
                                        FROM producto p 
                                        JOIN neumatico n ON p.id=n.id_producto');
            $stmt->execute();

            $neumaticos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $neumaticos;

        } catch(Exception $e){
            error_log("Error al cargar otros productos: ".$e->getMessage());
            throw $e;
            return;
        }
    }

    public function agregarStock(){
    }

    public function restarStock(){
    }
    
}