<?php

namespace Sigae\Controllers;
use Sigae\Models\OtroProducto;
use Sigae\Models\Producto;
use Sigae\Controllers\ControladorProducto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use DateTime;
use DateTimeZone;

class ControladorOtroProducto extends AbstractController{
    private $otroProducto;
    private $controladorProducto;

    function addNewAccessory(){
        $response=['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $productos = [];

        switch($rol){
            case 'gerente':
                if (isset($_POST["upc"], $_POST["precio"], $_POST["marca"], $_POST["nombre"]) && 
                    !empty($_POST["upc"]) && !empty($_POST["precio"]) && 
                    !empty($_POST["marca"]) && !empty($_POST["nombre"])) {

                    $upc = $_POST["upc"];
                    $precio = $_POST["precio"];
                    $marca = $_POST["marca"];
                    $nombre = $_POST["nombre"];

                    // Validar email
                    if (!$this->validarUpc($upc, 13)) {
                        $response['errors'][] = "Por favor, ingrese un código UPC válido.";
                    } elseif (!$this->validarPrecio($precio)) {
                        $response['errors'][] = "Por favor, ingrese un precio válido.";
                    } elseif (!$this->validarMarcaNombre($marca, 23)) {
                        $response['errors'][] = "Por favor, ingrese una marca válida.";
                    } elseif (!$this->validarMarcaNombre($nombre, 23)) {
                        $response['errors'][] = "Por favor, ingrese un nombre válido.";
                    } elseif (Producto::existeUpc($rol, $upc)) {
                        $response['errors'][] = "Ya existe un producto con el código UPC ingresado.";
                    } else {
                        $fecha = $this->obtenerFechaHoraActual();

                        // Instancia vehiculo con datos ingresados
                        $this->otroProducto = new OtroProducto($nombre, null, $upc, $precio, $marca, $fecha, 0, false);

                        // Inicializar una conexión PDO como cliente
                        $this->otroProducto->setDBConnection("gerente");
                        $this->otroProducto->comenzarTransaccion();

                        try{
                            $this->otroProducto->agregar();
                            
                            // Confirmar la transacción realizada
                            $this->otroProducto->confirmarTransaccion();
                            $response['success'] = true;

                        } catch(Exception $e){
                            $this->otroProducto->deshacerTransaccion();
                            $response['errors'][] = "Error procesando el producto: ". $e->getMessage();
                        } finally{
                            $this->otroProducto->cerrarDBConnection();
                        }
                    }
                } else {
                    $response['errors'][] = "Debe llenar todos los campos.";
                }

                try{
                    $this->controladorProducto = new ControladorProducto();
                    $productos = $this->controladorProducto->getProductosTodos('gerente');
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

    function editAccessory(){
        $response=['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $productos = [];

        switch($rol){
            case 'gerente':
                if (isset($_POST["upc"], $_POST["precio"], $_POST["marca"], $_POST["nombre"], $_POST["upc"], $_POST["id"]) && 
                    !empty($_POST["id"]) && !empty($_POST["upc"]) && !empty($_POST["precio"]) && 
                    !empty($_POST["marca"]) && !empty($_POST["nombre"]) ) {

                    $id = $_POST["id"];
                    $upc = $_POST["upc"];
                    $precio = $_POST["precio"];
                    $marca = $_POST["marca"];
                    $nombre = $_POST["nombre"];

                    if (!$this->validarId($upc)) {
                        $response['errors'][] = "Por favor, ingrese un ID válido.";
                    } elseif (!$this->validarUpc($upc, 13)) {
                        $response['errors'][] = "Por favor, ingrese un código UPC válido.";
                    } elseif (!$this->validarPrecio($precio)) {
                        $response['errors'][] = "Por favor, ingrese un precio válido.";
                    } elseif (!$this->validarMarcaNombre($marca, 23)) {
                        $response['errors'][] = "Por favor, ingrese una marca válida.";
                    } elseif (!$this->validarMarcaNombre($nombre, 23)) {
                        $response['errors'][] = "Por favor, ingrese un nombre válido.";
                    } elseif (!Producto::existeUpc($rol, $upc)) {
                        $response['errors'][] = "No existe un producto con el código UPC ingresado.";
                    } else {
                        $fecha = $this->obtenerFechaHoraActual();

                        // Instancia vehiculo con datos ingresados
                        $this->otroProducto = new OtroProducto($nombre, $id, $upc, $precio, $marca, $fecha, 0);

                        // Inicializar una conexión PDO como cliente
                        $this->otroProducto->setDBConnection("gerente");
                        $this->otroProducto->comenzarTransaccion();

                        try{
                            $this->otroProducto->modificar();
                            
                            // Confirmar la transacción realizada
                            $this->otroProducto->confirmarTransaccion();
                            $response['success'] = true;

                        } catch(Exception $e){
                            $this->otroProducto->deshacerTransaccion();
                            $response['errors'][] = "Error procesando el producto: ". $e->getMessage();
                        } finally{
                            $this->otroProducto->cerrarDBConnection();
                        }
                    }
                } else {
                    $response['errors'][] = "Debe llenar todos los campos.";
                }

                try{
                    $this->controladorProducto = new ControladorProducto();
                    $productos = $this->controladorProducto->getProductosTodos('gerente');
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

    function deleteAccessory(){
        $response=['success' => false, 'errors' => []];
    }


    function getOtrosProductos($rol){
        $otrosProd=[];
        try{
            if($rol == 'cliente'){
                $otrosProd = OtroProducto::getProductosCategoriaDisp($rol);
            } elseif($rol == 'gerente' || 'cajero') {
                // Si es funcionario, accede a más datos
                $otrosProd = OtroProducto::getProductosCategoriaDetallados($rol);
            }
        } catch(Exception $e){
            throw $e;
        } finally {
            return $otrosProd;
        }
    }

    private function obtenerFechaHoraActual(){
        $uruguayTimezone = new DateTimeZone('America/Montevideo');
        $dtActual = new DateTime('now', $uruguayTimezone);

        // Formatear la fecha
        return $dtActual->format('Y-m-d H:i:s');
    }

    private function validarId($id) {
        /* Verifica si el $id es numerico */
        return (preg_match("/^\d+$/", $id));
    }

    private function validarPrecio($precio) {
        /* Verifica si el precio cumple con ciertos criterios:
            - Solo pueden ser caracteres numéricos
            - Valida dos dígitos decimales
            - Hasta 10 dígitos enteros
            - El mínimo valor es 0,00
        */
        return (preg_match("/^(?!0\d)(\d{1,10})(\.\d{2})?$/", $precio));
    }

    private function validarMarcaNombre($str, $max) {
        /* Verifica si la cadena $str cumple con ciertos criterios de caracteres (contiene alfabético, admite guiones)
        y si la extension de la cadena es menor o igual al maximo especificado por la variable $max. */
        return (preg_match("/^[a-zA-Z -]+$/", $str) && strlen($str) <= $max);
    }

    private function validarUpc($upc){
        return preg_match("/^[0-9]{8,13}$/", $upc);
    }

}