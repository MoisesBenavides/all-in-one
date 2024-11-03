<?php

namespace Sigae\Models;
use function Sigae\Config\conectarDB;
use PDO;
use Exception;

class OtroProducto extends Producto{
    private ?PDO $conn =null;
    private $nombre;

    public function __construct($nombre = null, $id = null, $upc = null, $precio = null, 
                                $marca = null, $fecha_creacion = null, $stock = null){
        parent::__construct($id, $upc, $precio, $marca, $fecha_creacion, $stock);
        $this->nombre = $nombre;
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

    public function getNombre(){
        return $this->nombre;
    }

    public function setNombre($nombre){
        $this->nombre = $nombre;

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
            $stmt = $this->conn->prepare('SELECT p.id, p.precio, p.marca, p.fecha_creacion, op.nombre 
                                        FROM producto p 
                                        JOIN otro_producto op ON p.id=op.id_producto
                                        WHERE p.stock > 0');
            $stmt->execute();
            $otrosProductos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $otrosProductos;

        } catch (Exception $e){
            error_log("Error al cargar otros productos: ".$e->getMessage());
            throw $e;
            return;
        }
    }

    public function getProductosDetallados(){
        try{
            $stmt = $this->conn->prepare('SELECT p.id, p.upc, p.precio, 
                                                p.marca, p.fecha_creacion, p.stock, 
                                                op.nombre 
                                        FROM producto p 
                                        JOIN otro_producto op ON p.id=op.id_producto
                                        ORDER BY p.id DESC');
            $stmt->execute();
            $otrosProductos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $otrosProductos;

        } catch (Exception $e){
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