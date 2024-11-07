<?php

namespace Sigae\Controllers;
use Sigae\Models\Producto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class ControladorProducto extends AbstractController{
    private $conn;

    public function continuarTransaccion($conn){
        $this->conn = $conn;
    }

    public function archiveProduct(): Response{
        $response=['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $productos = [];

        switch($rol){
            case 'gerente':
                if (isset($_POST["id"]) && !empty($_POST["id"])) {

                    $id = $_POST["id"];

                    if (!$this->validarId($id)) {
                        $response['errors'][] = "Por favor, ingrese un ID válido.";
                    } else {
                        try{
                            if (!Producto::existeId($rol, $id)){
                                throw new Exception("No existe un producto registrado con el ID: " . $id);
                            } else{
                                Producto::archivar($rol, $id); 
                                $response['success'] = true;
                            }
                        } catch(Exception $e){
                            $response['errors'][] = "Error al archivar el producto: ".$e->getMessage();
                        }
                    }
                } else {
                    $response['errors'][] = "Debe ingresar el ID del producto.";
                }

                try{
                    $productos = $this->getProductosTodos('gerente');
                } catch(Exception $e){
                    $response['errors'][] = $e->getMessage();
                }

                return $this->render('employee/manager/inventario.html.twig', [
                    'productos' => $productos,
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    public function sumarStock($id, $cantidad){
        try {
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
            $stockActual = Producto::obtenerStock($this->conn, $id);
            if ($stockActual == 0) {
                throw new Exception("No hay disponibilidad.");
            } elseif (($stockActual - $cantidad) < 0) {
                throw new Exception("Límite mínimo de stock excedido.");
            } else {
                $nuevoStock = $stockActual - $cantidad;
                Producto::modificarStock($this->conn, $id, $nuevoStock);
            }  
            
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function existeId($rol, $id){
        return Producto::existeId($rol, $id);
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

    private function validarId($id) {
        /* Verifica si el id es numerico */
        return (preg_match("/^\d+$/", $id));
    }

}