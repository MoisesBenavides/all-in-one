<?php

namespace Sigae\Models;
use function Sigae\Config\conectarDB;
use PDO;
use PDOException;
use Exception;

abstract class Producto{
    protected ?PDO $conn =null;
    protected $id;
    protected $upc;
    protected $precio;
    protected $marca;
    protected $fecha_creacion;
    protected $stock;

    public function __construct($id = null, $upc = null, $precio = null, 
                                $marca = null, $fecha_creacion = null, $stock = null) {
        $this->id = $id;
        $this->upc = $upc;
        $this->precio = $precio;
        $this->marca = $marca;
        $this->fecha_creacion = $fecha_creacion;
        $this->stock = $stock;
    }
    
    public function getId(){
        return $this->id;
    }

    public function setId($id){
        $this->id = $id;
    }

    public function getUpc(){
        return $this->upc;
    }

    public function setUpc($upc){
        $this->upc = $upc;
    }

    public function getPrecio(){
        return $this->precio;
    }

    public function setPrecio($precio){
        $this->precio = $precio;
    }

    public function getMarca(){
        return $this->marca;
    }

    public function setMarca($marca){
        $this->marca = $marca;
    }

    public function getFechaCreacion(){
        return $this->fecha_creacion;
    }

    public function setFechaCreacion($fecha_creacion){
        $this->fecha_creacion = $fecha_creacion;
    }

    public function getStock(){
        return $this->stock;
    }

    public function setStock($stock){
        $this->stock = $stock;
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

    abstract public static function getProductosCategoriaDisp($rol);

    abstract public static function getProductosCategoriaDetallados($rol);

    public static function getProductosDisp($rol){
        try{
            $conn = conectarDB($rol);
            if($conn === false){
                throw new Exception("No se puede conectar con la base de datos.");
            }

            $stmt = $conn->prepare('SELECT p.id, p.precio, p.marca, p.fecha_creacion, 
                                                n.tamano, n.modelo, n.tipo, 
                                                op.nombre 
                                        FROM producto p 
                                        LEFT JOIN neumatico n ON p.id=n.id_producto 
                                        LEFT JOIN otro_producto op ON p.id=op.id_producto 
                                        WHERE p.stock > 0');
            $stmt->execute();

            $neumaticos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $neumaticos;

        } catch(Exception $e){
            error_log("Error al cargar otros neumáticos: ".$e->getMessage());
            throw $e;
            return;
        } finally {
            $conn = null;
        }
    }

    public static function getProductosDetallados($rol){
        try{
            $conn = conectarDB($rol);
            if($conn === false){
                throw new Exception("No se puede conectar con la base de datos.");
            }

            $stmt = $conn->prepare('SELECT p.id, p.upc, p.precio, 
                                                p.marca, p.fecha_creacion, p.stock, 
                                                n.tamano, n.modelo, n.tipo, 
                                                op.nombre 
                                        FROM producto p 
                                        LEFT JOIN neumatico n ON p.id=n.id_producto 
                                        LEFT JOIN otro_producto op ON p.id=op.id_producto 
                                        ORDER BY p.id DESC');
            $stmt->execute();

            $neumaticos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $neumaticos;

        } catch(Exception $e){
            error_log("Error al cargar productos: ".$e->getMessage());
            throw $e;
            return;
        } finally {
            $conn = null;
        }
    }

    public static function existeId($rol, $id){
        $conn = conectarDB($rol);
        if($conn === false){
            throw new Exception("No se puede conectar con la base de datos.");
        }

        try{
            $stmt = $conn->prepare('SELECT COUNT(*) FROM producto WHERE id = :id');
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $count = $stmt->fetchColumn();

            return $count != 0;

        } catch(Exception $e){
            throw "Error al verificar un producto existente por id: ".$e;
        } finally {
            $conn = null;
        }
    }

    public static function modificarStock($rol, $id, $nuevoStock){
        try{
            $conn = conectarDB($rol);
            if($conn === false){
                throw new Exception("No se puede conectar con la base de datos.");
            }

            $stmt = $conn->prepare('UPDATE producto 
                                    SET stock = :nStock 
                                    WHERE id = :id');
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':nStock', $nuevoStock);
            $stmt->execute();
            return true;

        } catch(Exception $e){
            error_log("Error al modificar el stock: ".$e->getMessage());
            throw $e;
            return false;
        } finally{
            $conn = null;
        }
    }

    public static function obtenerStock($rol, $id){
        $conn = conectarDB($rol);
        if($conn === false){
            throw new Exception("No se puede conectar con la base de datos.");
        }

        try{
            $stmt = $conn->prepare('SELECT stock FROM producto WHERE id = :id');
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $stock = $stmt->fetchColumn();

            return $stock;

        } catch(Exception $e){
            throw "Error al obtener stock del producto: ".$e;
        } finally {
            $conn = null;
        }
    }
}