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
                                    $precio = null, $marca = null, $fecha_creacion = null, $stock = null){
            parent::__construct($id, $upc, $precio, $marca, $fecha_creacion, $stock);
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