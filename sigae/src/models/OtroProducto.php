<?php

namespace Sigae\Models;
use function Sigae\Config\conectarDB;
use PDO;
use Exception;

class OtroProducto extends Producto{
    private $nombre;

    public function __construct($nombre = null, $id = null, $upc = null, $precio = null, 
                                $marca = null, $fecha_creacion = null, $stock = null, $archivado = null){
        parent::__construct($id, $upc, $precio, $marca, $fecha_creacion, $stock, $archivado);
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
        $archivado = !empty($archivado) ? (int)$archivado : 0;

        try {
            $stmt = $this->conn->prepare('INSERT INTO producto (upc, precio, marca, fecha_creacion, stock, archivado) 
                                    VALUES (:upc, :precio, :marca, :fecha_crea, :stock, :arch)');
    
            $stmt->bindParam(':upc', $upc);
            $stmt->bindParam(':precio', $precio);
            $stmt->bindParam(':marca', $marca);
            $stmt->bindParam(':fecha_crea', $fecha_creacion);
            $stmt->bindParam(':stock', $stock);
            $stmt->bindParam(':arch', $archivado);
                
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
            $stmt = $this->conn->prepare('INSERT INTO otro_producto (id_producto, nombre) 
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

    public function modificar() {
        $id = $this->getId();
        $upc = $this->getUpc();
        $precio = $this->getPrecio();
        $marca = $this->getMarca();
        $stock = $this->getStock();

        try {
            $stmt = $this->conn->prepare('UPDATE producto 
                                            SET upc=:upc, precio=:precio, marca=:marca, stock=:stock 
                                            WHERE id=:id');
    
            $stmt->bindParam(':upc', $upc);
            $stmt->bindParam(':precio', $precio);
            $stmt->bindParam(':marca', $marca);
            $stmt->bindParam(':stock', $stock);
            $stmt->bindParam(':id', $id);
                
            $stmt->execute();
    
            return $this->modificarOtroProducto() !== false;
    
        } catch (Exception $e) {
            throw new Exception("Error agregando el producto: ".$e->getMessage());
            return false; 
        }
    }

    public function modificarOtroProducto() {
        $id_prod = $this->getId();
        $nombre = $this->getNombre();

        try {
            $stmt = $this->conn->prepare('UPDATE otro_producto SET nombre=:nom WHERE id_producto=:id');
    
            $stmt->bindParam(':id', $id_prod);
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
                                        WHERE p.stock > 0 AND p.archivado=0');
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
                                        WHERE p.archivado=0 
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