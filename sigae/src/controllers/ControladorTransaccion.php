<?php

namespace Sigae\Controllers;
use Sigae\Models\Transaccion;
use Sigae\Models\TipoTransaccion;
use Sigae\Controllers\ControladorProducto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use PDOException;
use Exception;
use DateTime;
use DateTimeZone;

class ControladorTransaccion extends AbstractController{
    private $transaccion;
    private $controladorProducto;

    public function __construct(){
        $this->controladorProducto=new ControladorProducto();
    }

    function createTransaction(): Response{
        $response=['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        
        switch($rol){
            case 'gerente':
                if(isset($_POST["tipoTr"], $_POST["idProd"], $_POST["cantidad"]) && 
                !empty($_POST["tipoTr"]) && !empty($_POST["idProd"]) && !empty($_POST["cantidad"])){

                    $tipoTr = $_POST["tipoTr"];
                    $idProd = $_POST["idProd"];
                    $cantidad = $_POST["cantidad"];

                    if(!$this->validarTipo($tipoTr)){
                        $response['errors'][] = "Por favor, seleccione un tipo de transacción válido.";
                    } elseif(!$this->validarIdProd($idProd)){
                        $response['errors'][] = "Por favor, seleccione un ID de producto válido.";
                    } elseif(!$this->validarCantidad($cantidad, 6)){
                        $response['errors'][] = "Por favor, ingrese una cantidad de productos válida.";
                    } elseif(!$this->controladorProducto->existeId($rol, $idProd)){
                        $response['errors'][] = "No existe un producto con el ID ingresado.";
                    } else{
                        $fecha = $this->obtenerFechaHoraActual();

                        $this->transaccion = new Transaccion(null, TipoTransaccion::tryFrom($tipoTr), $cantidad, $fecha);

                        try{
                            $this->transaccion->setDBConnection($rol);
                            $this->transaccion->comenzarTransaccion();

                            if(!$this->transaccion->registrarTransaccion($idProd)){
                                throw new Exception("Error al registrar la transacción.");
                            } else {
                                $this->controladorProducto->setDBConnection($this->transaccion->getDBConnection());

                                if ($tipoTr == 'ingreso'){
                                    $this->controladorProducto->sumarStock($idProd, $cantidad);
                                } elseif($tipoTr == 'egreso'){
                                    $this->controladorProducto->restarStock($idProd, $cantidad);
                                }
                                
                                $this->transaccion->confirmarTransaccion();
                                $response['success'] = true;

                                $transaccion = [
                                    'id' => $this->transaccion->getId(),
                                    'idProd' => $idProd,
                                    'tipo' => $this->transaccion->getTipo(),
                                    'cantidad' => $this->transaccion->getCantidad(),
                                    'fecha' => $this->transaccion->getFecha(),
                                ];

                                // Carga la transacción en la página de confirmación
                                return $this->render('employee/manager/transaccionConfirmacion.html.twig', [
                                    'transaccion' => $transaccion
                                ]);
                            }
                        } catch (Exception $e){
                            $this->transaccion->deshacerTransaccion();
                            $response['errors'][] = $e->getMessage();
                        } finally{
                            $this->transaccion->cerrarDBConnection();
                        }
                    }
                } else {
                    $response['errors'][] = "Debe llenar todos los campos.";
                }
                return $this->render('employee/manager/transaccionStock.html.twig', [
                    'response' => $response  // Aquí pasa la respuesta a la vista
                ]);
                
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    function transactionConfirmation(){
        $response=['success' => false, 'errors' => []];

    }

    private function obtenerFechaHoraActual(){
        $uruguayTimezone = new DateTimeZone('America/Montevideo');
        $dtActual = new DateTime('now', $uruguayTimezone);

        // Formatear la fecha
        return $dtActual->format('Y-m-d H:i:s');
    }

    private function validarCantidad($cantidad, $max) {
        // Valida si la cantidad (sin ceros iniciales) es menor o igual al máximo $max de dígitos
        $numeroSinCeros = ltrim($cantidad, '0');
        return filter_var($numeroSinCeros, FILTER_VALIDATE_INT) !== false && strlen($numeroSinCeros) <= $max;
    }
    
    private function validarIdProd($id){
        return (preg_match("/^\d+$/", $id));
    }

    private function validarTipo($tipoTr){
        // Valida si es un tipo de transaccion del enum TipoTransaccion
        return TipoTransaccion::tryFrom($tipoTr) !== null;
    }
}