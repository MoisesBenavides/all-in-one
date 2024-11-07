<?php

namespace Sigae\Models;
use function Sigae\Config\conectarDB;
use PDO;
use Exception;

class Neumatico extends Producto{
    private $tamano;
    private $modelo;
    private $tipo;
    
        public function __construct($tamano = null, $modelo = null, $tipo = null, $id = null, $upc = null, 
                                    $precio = null, $marca = null, $fecha_creacion = null, $stock = null, $archivado = null){
            parent::__construct($id, $upc, $precio, $marca, $fecha_creacion, $stock, $archivado);
            $this->tamano = $tamano;
            $this->modelo = $modelo;
            $this->tipo = $tipo;
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
    
            return $this->agregarNeumatico() !== false;
    
        } catch (Exception $e) {
            throw new Exception("Error agregando el producto: ".$e->getMessage());
            return false; 
        }
    }

    public function agregarNeumatico() {
        $id = $this->getId();
        $tamano = $this->getTamano();
        $modelo = $this->getModelo();
        $tipo = $this->getTipo();

        try {
            $stmt = $this->conn->prepare('INSERT INTO neumatico (id_producto, tamano, modelo, tipo) 
                                    VALUES (:id, :taman :mod, :tip)');
    
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':taman', $tamano);
            $stmt->bindParam(':mod', $modelo);
            $stmt->bindParam(':tip', $tipo);
                
            $stmt->execute();
    
            return true;
    
        } catch (Exception $e) {
            throw new Exception("Error agregando el neumatico: ".$e->getMessage());
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
            $stmt = $this->conn->prepare('UPDATE producto SET upc=:upc, precio=:precio, marca=:marca, stock=:stock) 
                                        WHERE id=:id');
    
            $stmt->bindParam(':upc', $upc);
            $stmt->bindParam(':precio', $precio);
            $stmt->bindParam(':marca', $marca);
            $stmt->bindParam(':stock', $stock);
            $stmt->bindParam(':id', $id);
                
            $stmt->execute();
    
            return $this->modificarNeumatico() !== false;
    
        } catch (Exception $e) {
            throw new Exception("Error agregando el producto: ".$e->getMessage());
            return false; 
        }
    }

    public function modificarNeumatico() {
        $id_prod = $this->getId();
        $tamano = $this->getTamano();
        $modelo = $this->getModelo();
        $tipo = $this->getTipo();

        try {
            $stmt = $this->conn->prepare('UPDATE neumatico SET tamano=:taman, modelo=:mod, tipo=:tip 
                                        WHERE id_producto=:id');
    
            $stmt->bindParam(':id', $id_prod);
            $stmt->bindParam(':taman', $tamano);
            $stmt->bindParam(':mod', $modelo);
            $stmt->bindParam(':tip', $tipo);
                
            $stmt->execute();
    
            return true;
    
        } catch (Exception $e) {
            throw new Exception("Error agregando el neumatico: ".$e->getMessage());
            return false;
        }
    }

    public static function getProductosCategoriaDisp($rol){
        try{
            $conn = conectarDB($rol);
            if($conn === false){
                throw new Exception("No se puede conectar con la base de datos.");
            }

            $stmt = $conn->prepare('SELECT p.id, p.precio, p.marca, p.fecha_creacion, 
                                                n.tamano, n.modelo, n.tipo
                                        FROM producto p 
                                        JOIN neumatico n ON p.id=n.id_producto
                                        WHERE p.stock > 0 AND p.archivo=0');
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

    public static function getProductosCategoriaDetallados($rol){
        try{
            $conn = conectarDB($rol);
            if($conn === false){
                throw new Exception("No se puede conectar con la base de datos.");
            }
            
            $stmt = $conn->prepare('SELECT p.id, p.upc, p.precio, 
                                                p.marca, p.fecha_creacion, p.stock, 
                                                n.tamano, n.modelo, n.tipo
                                        FROM producto p 
                                        JOIN neumatico n ON p.id=n.id_producto 
                                        WHERE p.archivado=0 
                                        ORDER BY p.id DESC');
            $stmt->execute();
            $neumaticos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $neumaticos;

        } catch(Exception $e){
            error_log("Error al cargar neumáticos: ".$e->getMessage());
            throw $e;
            return;
        } finally {
            $conn = null;
        }
    }    
}