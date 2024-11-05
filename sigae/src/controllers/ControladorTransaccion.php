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

    function createTransaction(): Response|RedirectResponse{
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
                    } elseif(!$this->validarCantidad($cantidad)){
                        $response['errors'][] = "Por favor, ingrese una cantidad de productos válida.";
                    } else{
                        $fecha = $this->obtenerFechaHoraActual();

                        $this->transaccion = new Transaccion($tipoTr, $cantidad, $fecha);
                        $this->transaccion->setDBConnection("gerente");

                        try{
                            if(!$this->transaccion->registrarTransaccion($idProd)){
                                throw new Exception("Error al registrar la transacción.");
                            } else {
                                $this->controladorProducto = new ControladorProducto();

                                if ($tipoTr == 'ingreso'){
                                    $this->controladorProducto->sumarStock($rol, $idProd, $cantidad);
                                } elseif($tipoTr == 'egreso'){
                                     $this->controladorProducto->restarStock($rol, $idProd, $cantidad);
                                }
                                
                                $this->transaccion->confirmarTransaccion();
                                $response['success'] = true;
                            }
                        } catch (Exception $e){
                            $this->transaccion->deshacerTransaccion();
                            $response['errors'][] = $e->getMessage();
                        } finally{
                            $this->transaccion->cerrarDBConnection();
                        }
                    }

                }
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }
    }

    private function obtenerFechaHoraActual(){
        $uruguayTimezone = new DateTimeZone('America/Montevideo');
        $dtActual = new DateTime('now', $uruguayTimezone);

        // Formatear la fecha
        return $dtActual->format('Y-m-d H:i:s');
    }

    // TODO: validar
    private function validarCantidad($cantidad){
        return true;
    }

    // TODO: validar
    private function validarIdProd($id){
        return true;
    }

    private function validarTipo($tipoTr){
        // Valida si es un tipo de transaccion del enum TipoTransaccion
        return TipoTransaccion::tryFrom($tipoTr) !== null;
    }
}