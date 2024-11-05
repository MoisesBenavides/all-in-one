<?php

namespace Sigae\Controllers;
use Sigae\Models\Producto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;

class ControladorProducto extends AbstractController{

    public function sumarStock($rol, $id, $cantidad){
        try{
            if (!Producto::existeId($rol, $id)){
                throw new Exception("No existe un producto registrado con el ID: " . $id);
            }
            $stockActual = Producto::obtenerStock($rol, $id);

            if(($stockActual + $cantidad) > 999999){
                throw new Exception("Límite máximo de stock excedido.");
            } else{
                $nuevoStock = $stockActual + $cantidad;
                Producto::modificarStock($rol, $id, $nuevoStock);
            }
        } catch(Exception $e){
            throw $e;
        }
    }

    public function restarStock($rol, $id, $cantidad){
        try{
            if (!Producto::existeId($rol, $id)){
                throw new Exception("No existe un producto registrado con el ID: " . $id);
            }
            $stockActual = Producto::obtenerStock($rol, $id);

            if ($stockActual == 0 || ($stockActual - $cantidad) < 0){
                throw new Exception("Límite mínimo de stock excedido.");            
            } else {
                $nuevoStock = $stockActual - $cantidad;
                Producto::modificarStock($rol, $id, $nuevoStock);
            }
        } catch(Exception $e){
            throw $e;
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