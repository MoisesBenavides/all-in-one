<?php

namespace Sigae\Controllers;
use Sigae\Models\Parking;
use Sigae\Models\TipoPlazaParking;
use Sigae\Controllers\ControladorVehiculo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Exception;
use DateTime;
use InvalidArgumentException;

class ControladorParking extends AbstractController{
    private const PATH_PRECIOS_JSON = __DIR__ . '/../data/preciosHoraParking.json';
    private $parking;
    private $preciosHora;
    private $controladorVehiculo;

    public function __construct(){
        $this->cargarPreciosHora(self::PATH_PRECIOS_JSON);
        $this->controladorVehiculo = new ControladorVehiculo();
    }

    function bookParkingSimple(): JsonResponse|RedirectResponse{
        session_start();
        $response=['success' => false, 'errors' => [], 'debug' => []];

        // Debug: Log all received data
        $response['debug']['received_data']=$_POST;

        // Validacion de campos vacios
        if (isset($_POST["fecha_inicio"], $_POST["fecha_final"], $_POST["tipoVehiculo"]) 
            && (!empty($_POST["matriculaYa"]) || !empty($_POST["matricula"])) &&
            !empty($_POST["fecha_inicio"]) && !empty($_POST["fecha_final"]) && !empty($_POST["tipoVehiculo"])) {
                
            $fecha_inicio = $_POST["fecha_inicio"];
            $fecha_final = $_POST["fecha_final"];
            $tipoVehiculo = strtolower($_POST["tipoVehiculo"]);

            try {
                // Validar solo una matrícula ingresada
                $matricula = $this->obtenerTipoRegistroMat(
                    // Si no definidas, entonces null
                    $_POST["matriculaYa"] ?? null, 
                    $_POST["matricula"] ?? null
                );
            } catch (InvalidArgumentException $e) {
                $response['errors'][] = $e->getMessage();
            }
    
            if (isset($matricula)) { // Verifica que la matrícula se haya definido correctamente
                $matricula = strtoupper($matricula);

                // Debug: Log processed data
                $response['debug']['processed_data'] = [
                    'fecha_inicio' => $fecha_inicio,
                    'fecha_final' => $fecha_final,
                    'tipoVehiculo' => $tipoVehiculo,
                    'matricula' => $matricula
                ];

                if (!$this->validarFecha($fecha_inicio)){
                    $response['errors'][] = "Por favor, ingrese una fecha de inicio válida.";
                } elseif (!$this->validarFecha($fecha_final) && (($this->obtenerMinutos($fecha_final, $fecha_inicio)) < 5)){
                    $response['errors'][] = "Por favor, ingrese una fecha final válida. Mínimo 5 minutos después que la de inicio.";
                } elseif(!$this->controladorVehiculo->validarTipoVehiculo($tipoVehiculo)){
                    $response['errors'][] = "El tipo de vehículo seleccionado no está disponible.";
                } elseif(!$this->controladorVehiculo->validarMatricula($matricula)){
                    $response['errors'][] = "Por favor, ingrese una matrícula válida.";
                } else {
                    // Obtener datos del tipo de servicio ingresado
                    $difFechas = $this->obtenerMinutos($fecha_final, $fecha_inicio);
                    $precio = $this->calcularPrecio($difFechas, $tipoVehiculo);
                    $tipo_plaza = $this->obtenerTipoPlaza($tipoVehiculo);

                    // Parsear fechas
                    $fecha_inicioParsed = str_replace('T', ' ', $fecha_inicio) . ':00';
                    $fecha_finalParsed = str_replace('T', ' ', $fecha_final) . ':00';
                
                    $id_cliente = $_SESSION['id'];

                    $this->parking = new Parking(false, $tipo_plaza, null, $precio, $fecha_inicioParsed, $fecha_finalParsed);
                    if (!$this->controladorVehiculo->registrarYaVehiculo($matricula, $tipoVehiculo, $id_cliente)){
                        $response['errors'][] = "Ya existe un vehiculo con la matricula ingresada.";
                    } elseif (!$this->parking->reservarServicio($matricula)){
                        $response['errors'][] = "Error al reservar servicio.";
                    } else {
                        $response['success'] = true;

                        // TODO: Enviar correo de confirmación
                        error_log(print_r($this->parking, true)); // Esto mostrará los datos de la reserva.
                        // Guardar la reserva en la sesión
                        $_SESSION['reserva'] = $this->parking;
                        $_SESSION['servicio'] = 'parking';
                        $_SESSION['matricula'] = $matricula;

                        error_log(print_r($this->parking, true)); // Esto mostrará los datos de la reserva.

                        // Redireccionar al usuario a la página de confirmación de reserva
                        return $this->redirectToRoute('parkingConfirmation');
                    }      
                }
            }
        } else {
            $response['errors'][] = "Debe llenar todos los campos.";
            // Debug: Log which fields are missing
            $response['debug']['missing_fields'] = array_diff(
                ['fecha_inicio', 'fecha_final', 'tipoVehiculo', 'matricula'],
                array_keys($_POST)
            );
        }
        return new JsonResponse($response); // Devuelve un JSON en caso de error
    }

    function bookParkingLongTerm(){
        session_start();
        $response=['success' => false, 'errors' => [], 'debug' => []];

        // Debug: Log all received data
        $response['debug']['received_data']=$_POST;

        // Validacion de campos vacios
        if (isset($_POST["fecha_inicio"], $_POST["fecha_final"], $_POST["tipoVehiculo"]) 
            && (!empty($_POST["matriculaYa"]) || !empty($_POST["matricula"])) &&
            !empty($_POST["fecha_inicio"]) && !empty($_POST["fecha_final"]) && !empty($_POST["tipoVehiculo"])) {
                
            $fecha_inicio = $_POST["fecha_inicio"];
            $fecha_final = $_POST["fecha_final"];
            $tipoVehiculo = strtolower($_POST["tipoVehiculo"]);

            try {
                // Validar solo una matrícula ingresada
                $matricula = $this->obtenerTipoRegistroMat(
                    // Si no definidas, entonces null
                    $_POST["matriculaYa"] ?? null, 
                    $_POST["matricula"] ?? null
                );
            } catch (InvalidArgumentException $e) {
                $response['errors'][] = $e->getMessage();
            }
    
            if (isset($matricula)) { // Verifica que la matrícula se haya definido correctamente
                $matricula = strtoupper($matricula);

                // Debug: Log processed data
                $response['debug']['processed_data'] = [
                    'fecha_inicio' => $fecha_inicio,
                    'fecha_final' => $fecha_final,
                    'tipoVehiculo' => $tipoVehiculo,
                    'matricula' => $matricula
                ];

                if (!$this->validarFecha($fecha_inicio)){
                    $response['errors'][] = "Por favor, ingrese una fecha de inicio válida.";
                } elseif (!$this->validarFecha($fecha_final) && (($this->obtenerMinutos($fecha_final, $fecha_inicio)) < 5)){
                    $response['errors'][] = "Por favor, ingrese una fecha final válida. Mínimo 5 minutos después que la de inicio.";
                } elseif(!$this->controladorVehiculo->validarTipoVehiculo($tipoVehiculo)){
                    $response['errors'][] = "El tipo de vehículo seleccionado no está disponible.";
                } elseif(!$this->controladorVehiculo->validarMatricula($matricula)){
                    $response['errors'][] = "Por favor, ingrese una matrícula válida.";
                } else {
                    // Obtener datos del tipo de servicio ingresado
                    $difFechas = $this->obtenerMinutos($fecha_final, $fecha_inicio);
                    $precio = $this->calcularPrecio($difFechas, $tipoVehiculo);
                    $tipo_plaza = $this->obtenerTipoPlaza($tipoVehiculo);

                    // Parsear fechas
                    $fecha_inicioParsed = str_replace('T', ' ', $fecha_inicio) . ':00';
                    $fecha_finalParsed = str_replace('T', ' ', $fecha_final) . ':00';
                
                    $id_cliente = $_SESSION['id'];

                    $this->parking = new Parking(true, $tipo_plaza, null, $precio, $fecha_inicioParsed, $fecha_finalParsed);
                    if (!$this->controladorVehiculo->registrarYaVehiculo($matricula, $tipoVehiculo, $id_cliente)){
                        $response['errors'][] = "Ya existe un vehiculo con la matricula ingresada.";
                    } elseif (!$this->parking->reservarServicio($matricula)){
                        $response['errors'][] = "Error al reservar servicio.";
                    } else {
                        $response['success'] = true;

                        // TODO: Enviar correo de confirmación
                        error_log(print_r($this->parking, true)); // Esto mostrará los datos de la reserva.
                        // Guardar la reserva en la sesión
                        $_SESSION['reserva'] = $this->parking;
                        $_SESSION['servicio'] = 'parking';
                        $_SESSION['matricula'] = $matricula;

                        error_log(print_r($this->parking, true)); // Esto mostrará los datos de la reserva.

                        // Redireccionar al usuario a la página de confirmación de reserva
                        return $this->redirectToRoute('parkingConfirmation');
                    }      
                }
            }
        } else {
            $response['errors'][] = "Debe llenar todos los campos.";
            // Debug: Log which fields are missing
            $response['debug']['missing_fields'] = array_diff(
                ['fecha_inicio', 'fecha_final', 'tipoVehiculo', 'matricula'],
                array_keys($_POST)
            );
        }
        return new JsonResponse($response); // Devuelve un JSON en caso de error
    }

    function parkingConfirmation(): Response{
        session_start();
        error_log($_SESSION['email']. " reservó un servicio de parking");
        error_log(print_r($_SESSION, true));

        $sessionData = [
            'email' => $_SESSION['email'],
            'id' => $_SESSION['id'],
            'nombre' => $_SESSION['nombre'],
            'apellido' => $_SESSION['apellido'],
            'telefono' => isset($_SESSION['telefono']),
            'servicio' => $_SESSION['servicio'],
            'reserva' => isset($_SESSION['reserva']) ? [
                'id' => $_SESSION['reserva']->getId(),
                'matricula' => $_SESSION['matricula'],
                'precio' => $_SESSION['reserva']->getPrecio(),
                'fecha_inicio' => $_SESSION['reserva']->getFecha_inicio(),
                'fecha_final' => $_SESSION['reserva']->getFecha_final(),
                'estado' => $_SESSION['reserva']->getEstado(),
                'largo_plazo' => $_SESSION['reserva']->getLargo_plazo(),
                'tipo_plaza' => $_SESSION['reserva']->getTipo_plaza()
            ] : null,
        ];
        // Imprimir los datos en pagina de confirmacion
        return $this->render('client/reservaConfirmacion.html.twig', ['sessionData' => $sessionData]);
    }


    private function calcularPrecio($difFechas, $tipoVehiculo) {
        $prHora = $this->preciosHora[$tipoVehiculo]['precio']; // Precio por hora según el tipo de vehículo
        $precioCalc = 0;
        
        // Se cobra mínimo una hora completa
        if ($difFechas <= 60) {
            $precioCalc = $prHora;
        } else {
            // Se resta la primer hora aplicada...
            $precioCalc = $prHora;
            $difFechas -= 60;
            
            while ($difFechas > 0) {
                if ($difFechas < 15) {
                    // Si no han pasado 15 minutos después de la primera hora, se cobra una hora completa
                    $precioCalc += $prHora;
                    break;
                } elseif ($difFechas < 45) {
                    // Si la diferencia está entre 15 y 45 minutos, cobramos media hora adicional
                    $precioCalc += $prHora / 2;
                    break;
                } else {
                    // Si es igual o pasa 45 minutos se cobra una hora completa adicional
                    $precioCalc += $prHora;
                    $difFechas -= 60; // Se resta la hora completa
                }
            }
        }
    
        return $precioCalc;
    }
    
    private function obtenerTipoPlaza($tipoVehiculo) {
        return $tipoVehiculo !== 'moto' ? TipoPlazaParking::auto : TipoPlazaParking::moto;
    }
    
    private function obtenerMinutos($fecha_final, $fecha_inicio){
        $datetime1 = new DateTime($fecha_inicio);
        $datetime2 = new DateTime($fecha_final);
        $interval = $datetime1->diff($datetime2);
        return ($interval->days * 24 * 60 + $interval->h * 60 + $interval->i);
    }

    private function validarFecha($fecha){
        $formato = 'Y-m-d\TH:i'; // Formato de datetime-local (ej. 2024-10-22T19:30)
    
        // Intentamos crear un objeto DateTime con string de la fecha
        $dt = DateTime::createFromFormat($formato, $fecha);
        
        // Validar si el formato es válido
        return $dt && $dt->format($formato) === $fecha;
    }

    private function obtenerTipoRegistroMat($matRegistrarYa, $matVehiculoSelect){
        // Si ambos están definidos pero vacíos
        if (empty($matRegistrarYa) && empty($matVehiculoSelect)) {
            throw new InvalidArgumentException("Debe ingresar una matrícula.");
        } elseif (isset($matRegistrarYa) && !isset($matVehiculoSelect)) {
            $this->registrarYa=true;
            return $matRegistrarYa;  // Retorna la matricula a registrar, si es la unica esta seteada
        } elseif (!isset($matRegistrarYa) && isset($matVehiculoSelect)) {
            $this->registrarYa=false;
            return $matVehiculoSelect;  // Retorna la matricula del vehículo seleccionado por el cliente, si es la unica seteada
        } else {
            // Si ambos están seteados o ninguno está seteado, lanza un error
            throw new InvalidArgumentException("Debe ingresar sólo una matricula.");
        }
    }

    private function cargarPreciosHora($pathPreciosJson){

        if (!file_exists($pathPreciosJson)) {
            throw $this->createNotFoundException('El archivo de precios no existe.');
        }

        $contenidoJson = file_get_contents($pathPreciosJson);
        $precios = json_decode($contenidoJson, true);

        if ($precios === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error al decodificar el JSON: ' . json_last_error_msg());
        } else
            $this->preciosHora = $precios;
    }

}