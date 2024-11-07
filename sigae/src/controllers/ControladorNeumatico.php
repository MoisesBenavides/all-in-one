<?php

namespace Sigae\Controllers;
use Sigae\Models\Neumatico;
use Sigae\Models\Producto;
use Sigae\Controllers\ControladorProducto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use DateTime;
use DateTimeZone;

class ControladorNeumatico extends AbstractController{
    private $neumatico;
    private $controladorProducto;

    function addNewTyre(): Response{
        $response=['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        $productos = [];

        switch($rol){
            case 'gerente':
                if (isset($_POST["upc"], $_POST["precio"], $_POST["marca"], 
                    $_POST["modelo"], $_POST["tamano"], $_POST["tipo"]) && 
                    !empty($_POST["upc"]) && !empty($_POST["precio"]) && !empty($_POST["marca"]) && 
                    !empty($_POST["modelo"]) && !empty($_POST["tamano"]) && !empty($_POST["tipo"])) {

                    $upc = $_POST["upc"];
                    $precio = $_POST["precio"];
                    $marca = $_POST["marca"];
                    $modelo = $_POST["modelo"];
                    $tamano = $_POST["tamano"];
                    $tipo = $_POST["tipo"];

                    // Validar email
                    if (!$this->validarUpc($upc, 13)) {
                        $response['errors'][] = "Por favor, ingrese un código UPC válido.";
                    } elseif (!$this->validarPrecio($precio)) {
                        $response['errors'][] = "Por favor, ingrese un precio válido.";
                    } elseif (!$this->validarMarcaModelo($marca, 23)) {
                        $response['errors'][] = "Por favor, ingrese una marca válida.";
                    } elseif (!$this->validarMarcaModelo($modelo, 23)) {
                        $response['errors'][] = "Por favor, ingrese un modelo válido.";
                    } elseif (!$this->validarTamano($tamano)) {
                        $response['errors'][] = "Por favor, ingrese un tamaño con formato válido.";
                    } elseif (!$this->validarTipo($tipo)) {
                        $response['errors'][] = "Por favor, ingrese un tipo válido.";
                    } elseif (Producto::existeUpc($rol, $upc)) {
                        $response['errors'][] = "Ya existe un producto con el código UPC ingresado.";
                    } else {
                        $fecha = $this->obtenerFechaHoraActual();

                        // Instancia vehiculo con datos ingresados
                        $this->neumatico = new Neumatico($tamano, $modelo, $tipo, null, $upc, $precio, $marca, $fecha, 0);

                        // Inicializar una conexión PDO como cliente
                        $this->neumatico->setDBConnection("gerente");
                        $this->neumatico->comenzarTransaccion();

                        try{
                            $this->neumatico->agregar();

                            // Confirmar la transacción realizada
                            $this->neumatico->confirmarTransaccion();
                            $response['success'] = true;

                        } catch(Exception $e){
                            $this->neumatico->deshacerTransaccion();
                            $response['errors'][] = "Error procesando el producto: ". $e->getMessage();
                        } finally{
                            $this->neumatico->cerrarDBConnection();
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

    function deleteTyre(){
        $response=['success' => false, 'errors' => []];
    }

    function editTyre(){
        $response=['success' => false, 'errors' => []];
    }

    function getNeumaticos($rol){
        $neumaticos=[];
        try{
            if($rol == 'cliente'){
                $neumaticos = Neumatico::getProductosCategoriaDisp($rol);
            } elseif ($rol == 'gerente' || 'cajero') {
                // Si es funcionario, accede a más datos
                $neumaticos = Neumatico::getProductosCategoriaDetallados($rol);
            }
        } catch(Exception $e){
            throw $e;
        } finally {
            return $neumaticos;
        }
    }

    private function obtenerFechaHoraActual(){
        $uruguayTimezone = new DateTimeZone('America/Montevideo');
        $dtActual = new DateTime('now', $uruguayTimezone);

        // Formatear la fecha
        return $dtActual->format('Y-m-d H:i:s');
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

    private function validarTamano($tamano) {
        /* Verifica si $tamano del neumático cumple con los siguientes criterios:
            - Sigue el patrón “AAA/PP-RR-CCC-VV”
            - A (AAA): Ancho del neumático en milímetros, de 1 a 3 dígitos
            - P (PP): Relación de aspecto del neumático, exactamente 2 dígitos
            - R (RR): Tamaño del rin en pulgadas, exactamente 2 dígitos
            - C (CCC): Índice de carga y símbolo de velocidad, de 1 a 3 caracteres alfanuméricos
            - V (VV): Rango de velocidad, de 1 a 2 letras
            - Longitud máxima total: 16 caracteres, incluyendo barras y guiones
        */
        return (preg_match("/^\d{1,3}\/\d{2}-\d{2}-[a-zA-Z0-9]{1,3}-[a-zA-Z]{1,2}$/", $tamano));
    }

    private function validarMarcaModelo($str, $max) {
        /* Verifica si la cadena $str cumple con ciertos criterios de caracteres (contiene alfabético, admite guiones)
        y si la extension de la cadena es menor o igual al maximo especificado por la variable $max. */
        return (preg_match("/^[a-zA-Z -]+$/", $str) && strlen($str) <= $max);
    }

    private function validarUpc($upc){
        return preg_match("/^[0-9]{8,13}$/", $upc);
    }

    private function validarTipo($tipo){
        return preg_match("/^[a-zA-Z]{2}$/", $tipo);
    }
}