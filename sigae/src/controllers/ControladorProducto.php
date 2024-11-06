<?php

namespace Sigae\Controllers;
use Sigae\Models\Producto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;

class ControladorProducto extends AbstractController{
    private $conn;

    public function continuarTransaccion($conn){
        $this->conn = $conn;
    }

    public function sumarStock($id, $cantidad){
        try {
            // Verificar existencia del ID de producto antes de continuar
            $this->existeId($id);
            
            $stockActual = Producto::obtenerStock($this->conn, $id);
            $nuevoStock = $stockActual + $cantidad;
            if ($nuevoStock > 999999) {
                throw new Exception("Límite máximo de stock excedido.");
            } else {
                $nuevoStock = $stockActual + $cantidad;
                Producto::modificarStock($this->conn, $id, $nuevoStock);
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function restarStock($id, $cantidad){
        try {
            // Verificar existencia del ID de producto antes de continuar
            $this->existeId($id);

            $stockActual = Producto::obtenerStock($this->conn, $id);
            if ($stockActual == 0 || ($stockActual - $cantidad) < 0) {
                throw new Exception("Límite mínimo de stock excedido.");
            } else {
                $nuevoStock = $stockActual - $cantidad;
                Producto::modificarStock($this->conn, $id, $nuevoStock);
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function existeId($id){
        try{
            if (!Producto::existeId($this->conn, $id)){
                throw new Exception("No existe un producto registrado con el ID: " . $id);
            }
            return true;
        } catch(Exception $e){
            throw $e;
            return false;
        }

    }

    function getProductosTodos($rol){
        $neumaticos=[];
        try{
            if($rol == 'cliente'){
                $neumaticos = Producto::getProductosDisp($rol);
            } elseif ($rol == 'gerente' || 'cajero') {
                // Si es funcionario, accede a más datos
                $neumaticos = Producto::getProductosDetallados($rol);
            }
        } catch(Exception $e){
            throw $e;
        } finally {
            return $neumaticos;
        }
    }

}