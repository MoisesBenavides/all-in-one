<?php

namespace Sigae\Models;
use function Sigae\Config\conectarDB;
use PDO;
use Exception;

class OtroProducto extends Producto{
    private $nombre;

    public function __construct($nombre = null, $id = null, $upc = null, $precio = null, 
                                $marca = null, $fecha_creacion = null, $stock = null){
        parent::__construct($id, $upc, $precio, $marca, $fecha_creacion, $stock);
        $this->nombre = $nombre;
    }

    public function getNombre(){
        return $this->nombre;
    }

    public function setNombre($nombre){
        $this->nombre = $nombre;

        return $this;
    }

    public function agregar() {
        $upc = $this->getUpc();
        $precio = $this->getPrecio();
        $marca = $this->getMarca();
        $fecha_creacion = $this->getFechaCreacion();
        $stock = $this->getStock();

        try {
            $stmt = $this->conn->prepare('INSERT INTO producto (upc, precio, marca, fecha_creacion, stock) 
                                    VALUES (:upc, :precio, :marca, :fecha_crea, :stock)');
    
            $stmt->bindParam(':upc', $upc);
            $stmt->bindParam(':precio', $precio);
            $stmt->bindParam(':marca', $marca);
            $stmt->bindParam(':fecha_crea', $fecha_creacion);
            $stmt->bindParam(':stock', $stock);
                
            $stmt->execute();
            $this->setId($this->conn->lastInsertId());
    
            return $this->agregarOtroProducto() !== false;
    
        } catch (Exception $e) {
            throw new Exception("Error agregando el producto: ".$e->getMessage());
            return false; 
        }
    }

    public function agregarOtroProducto() {
        $id = $this->getId();
        $nombre = $this->getNombre();

        try {
            $stmt = $this->conn->prepare('INSERT INTO neumatico (id_producto, nombre) 
                                    VALUES (:id, :nom)');
    
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':nom', $nombre);
                
            $stmt->execute();
    
            return true;
    
        } catch (Exception $e) {
            throw new Exception("Error agregando el accesorio: ".$e->getMessage());
            return false;
        }
    }

    public static function getProductosCategoriaDisp($rol){
        try{
            $conn = conectarDB($rol);
            if($conn === false){
                throw new Exception("No se puede conectar con la base de datos.");
            }

            $stmt = $conn->prepare('SELECT p.id, p.precio, p.marca, p.fecha_creacion, op.nombre 
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
        } finally {
            $conn = null;
        }
    }

    public static function getProductosCategoriaDetallados($rol){
        try{
            $conn = conectarDB($rol);
            if($conn === false){
                throw new Exception("No se puede conectar con la base de datos.");
            }

            $stmt = $conn->prepare('SELECT p.id, p.upc, p.precio, 
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
        } finally {
            $conn = null;
        }
    }

}