<?php

namespace Sigae\Controllers;
use Sigae\Models\Parking;
use Sigae\Models\Cliente;
use Sigae\Models\TipoPlazaParking;
use Sigae\Controllers\ControladorVehiculo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Exception;
use DateTime;
use InvalidArgumentException;

class ControladorParking extends AbstractController{
    private const PATH_PRECIOS_JSON = __DIR__ . '/../data/preciosHoraParking.json';
    private const PATH_PLAZAS_JSON = __DIR__ . '/../data/plazasParking.json';
    private const INACTIVIDAD_MAX_TRANSACCION = 300;
    private $parking;
    private $preciosHora;
    private $plazasMotoSimple;
    private $plazasMotoLargoPlazo;
    private $plazasAutoSimple;
    private $plazasAutoLargoPlazo;
    private $controladorVehiculo;
    private $registrarYa;

    public function __construct(){
        $this->cargarPreciosHora(self::PATH_PRECIOS_JSON);
        $this->cargarPlazasPorTipo(self::PATH_PLAZAS_JSON);
        $this->controladorVehiculo = new ControladorVehiculo();
    }

    function bookParkingSimple(): Response|RedirectResponse{
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

                    $datos_parking = [
                        'largo_plazo' => null,
                        'tipo_plaza' => $tipo_plaza,
                        'precio' => $precio,
                        'fecha_inicio' => $fecha_inicioParsed,
                        'fecha_final' => $fecha_finalParsed,
                        'matricula' => $matricula,
                        'tipoVehiculo' => $tipoVehiculo
                    ];

                    // Guardar variables de sesión para reservar posteriormente a la elección de plazas
                    $_SESSION['parking'] = $datos_parking;

                    $this->parking = new Parking(false, $tipo_plaza, null, null, $fecha_inicioParsed, $fecha_finalParsed);
                    
                    // Hacer una conexión de base de datos como cliente
                    $this->parking->setDBConnection("cliente");
                    $this->parking->comenzarTransaccion();

                    try{
                        if ($this->registrarYa && !$this->controladorVehiculo->registrarYaVehiculo($matricula, $tipoVehiculo, $id_cliente)){
                            throw new Exception($e->getMessage());
                        } else {
                            // Consultar plazas ocupadas
                            $plazasOcupadas = $this->parking->obtenerPlazasOcupadas();
                            $this->parking->confirmarTransaccion();

                            if ($tipoVehiculo == "moto"){
                                // Obtiene las plazas de moto para parking simple que no están ocupadas
                                $plazasLibres = array_diff($this->plazasMotoSimple, $plazasOcupadas);
                                // Si hay al menos una plaza libre, hay disponibilidad
                                $disponible = !empty($plazasLibres);
        
                            } elseif ($tipoVehiculo == "auto") {
                                // Obtiene las plazas de auto para parking simple que no están ocupadas
                                $plazasLibres = array_diff($this->plazasAutoSimple, $plazasOcupadas);
                                // Si hay al menos una plaza libre, hay disponibilidad
                                $disponible = !empty($plazasLibres);
                            } else {
                                // Obtiene las plazas de auto para parking simple que no están ocupadas
                                $plazasLibres = array_diff($this->plazasAutoSimple, $plazasOcupadas);
                                // Si hay al menos dos plazas disponibles, hay disponibilidad
                                $disponible = array_count_values($plazasLibres) >= 2;
                            }
        
                            if (!$disponible){
                                $response['errors'][] = "No hay plazas disponibles en este horario.";
                            } else {
                                $response['success'] = true;
                                $_SESSION['eleccionPlazaComienzo'] = time();
                                $_SESSION['plazasLibres'] = $plazasLibres;
        
                                // Redireccionar al usuario a la página de eleccion de plaza
                                return $this->render('client/eleccionPlazaParking.html.twig', [
                                    'plazasLibres' => $_SESSION['plazasLibres'],
                                    'tipoVehiculo' => $tipoVehiculo
                                ]);                        
                            }                       
                        }
                    } catch(Exception $e){
                        $response['errors'][] = $e->getMessage();
                    } finally{
                        $this->parking->cerrarDBConnection();
                    }                                            
                }
            }
        } else {
            $response['errors'][] = "Debe llenar todos los campos.";
        }
        $misVehiculos = Cliente::cargarMisVehiculos($_SESSION['id']);
        return $this->render('client/reservarParkingSimple.html.twig', [
            'response' => $response,
            'misVehiculos' => $misVehiculos
        ]);
    }

    //TODO: Actualizar reservas largo plazo
    function bookParkingLongTerm(): Response|RedirectResponse{
        // Validacion de campos vacios
        if (isset($_POST["fecha_inicio"], $_POST["tipoReserva"], $_POST["tipoVehiculo"]) 
            && (!empty($_POST["matriculaYa"]) || !empty($_POST["matricula"])) &&
            !empty($_POST["fecha_inicio"]) && !empty($_POST["tipoReserva"]) && !empty($_POST["tipoVehiculo"])) {
                
            $fecha_inicio = $_POST["fecha_inicio"];
            $tipoReserva = $_POST["tipoReserva"];
            // Obtiene la fecha final por el tipo de reserva de largo plazo (semanal, quincenal, mensual, anual)
            $fecha_final = $this->obtenerFechaFinal($fecha_inicio, $tipoReserva);
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

                if (!$this->validarFecha($fecha_inicio)){
                    $response['errors'][] = "Por favor, ingrese una fecha de inicio válida.";
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

                    $datos_parking = [
                        'largo_plazo' => true,
                        'tipo_plaza' => $tipo_plaza,
                        'precio' => $precio,
                        'fecha_inicio' => $fecha_inicioParsed,
                        'fecha_final' => $fecha_finalParsed,
                        'matricula' => $matricula,
                        'tipoVehiculo' => $tipoVehiculo
                    ];

                    // Guardar variables de sesión para reservar posteriormente a la elección de plazas
                    $_SESSION['parking'] = $datos_parking;
                    
                    $this->parking = new Parking(true, $tipo_plaza, null, null, $fecha_inicioParsed, $fecha_finalParsed);

                    // Hacer una conexión de base de datos como cliente
                    $this->parking->setDBConnection("cliente");
                    $this->parking->comenzarTransaccion();

                    try{
                        if ($this->registrarYa && !$this->controladorVehiculo->registrarYaVehiculo($matricula, $tipoVehiculo, $id_cliente)){
                            throw new Exception($e->getMessage());
                        } else {
                            // Consultar plazas ocupadas
                            $plazasOcupadas = $this->parking->obtenerPlazasOcupadas();
                            $this->parking->confirmarTransaccion();

                            if ($tipoVehiculo == "moto"){
                                // Obtiene las plazas de moto para parking simple que no están ocupadas
                                $plazasLibres = array_diff($this->plazasMotoLargoPlazo, $plazasOcupadas);
                                // Si hay al menos una plaza libre, hay disponibilidad
                                $disponible = !empty($plazasLibres);
                
                            } elseif ($tipoVehiculo == "auto") {
                                // Obtiene las plazas de auto para parking simple que no están ocupadas
                                $plazasLibres = array_diff($this->plazasAutoLargoPlazo, $plazasOcupadas);
                                // Si hay al menos una plaza libre, hay disponibilidad
                                $disponible = !empty($plazasLibres);
                            } else {
                                // Obtiene las plazas de auto para parking simple que no están ocupadas
                                $plazasLibres = array_diff($this->plazasAutoLargoPlazo, $plazasOcupadas);
                                // Si hay al menos dos plazas disponibles, hay disponibilidad
                                $disponible = array_count_values($plazasLibres) >= 2;
                            }
                
                            if (!$disponible){
                                $response['errors'][] = "No hay plazas disponibles en este horario.";
                            } else {
                                $response['success'] = true;
                                $_SESSION['eleccionPlazaComienzo'] = time();
                                $_SESSION['plazasLibres'] = $plazasLibres;

                                // Redireccionar al usuario a la página de eleccion de plaza
                                return $this->render('client/eleccionPlazaParking.html.twig', [
                                    'plazasLibres' => $_SESSION['plazasLibres'],
                                    'tipoVehiculo' => $tipoVehiculo
                                ]);                        
                            }              
                        }
                    } catch(Exception $e){
                        $this->parking->deshacerTransaccion();
                        $response['errors'][] = $e->getMessage();
                    } finally{
                        $this->parking->cerrarDBConnection();
                    } 
                }   
            }
        } else {
            $response['errors'][] = "Debe llenar todos los campos.";
        }
        $misVehiculos = Cliente::cargarMisVehiculos($_SESSION['id']);
        return $this->render('client/reservarParkingLargoPlazo.html.twig', [
            'response' => $response,
            'misVehiculos' => $misVehiculos
        ]);
    }

    function submitParking(): Response|RedirectResponse{
        $tiempoAhora = time();
        $difTiempo = $tiempoAhora - $_SESSION['eleccionPlazaComienzo'];

        // Tiempo expirado
        if ($difTiempo > $this::INACTIVIDAD_MAX_TRANSACCION) {
            //Debug
            error_log("Tiempo de transaccion de parking expirado");
            return $this->redirectToRoute('aioParking');
        } elseif (isset($_SESSION['parking'], $_POST["plazasSeleccionadas"])
            && !empty($_SESSION['parking']) && !empty($_POST["plazasSeleccionadas"])){

            $largo_plazo = $_SESSION['parking']['largo_plazo'];
            $tipo_plaza = $_SESSION['parking']['tipo_plaza'];
            $precio = $_SESSION['parking']['precio'];
            $fecha_inicio = $_SESSION['parking']['fecha_inicio'];
            $fecha_final = $_SESSION['parking']['fecha_final'];
            $matricula = $_SESSION['parking']['matricula'];
            $tipoVehiculo = $_SESSION['parking']["tipoVehiculo"];

            // Decodificar el JSON a array
            $plazas = json_decode($_POST["plazasSeleccionadas"], true);
            //Debug plazas
            error_log('Plazas recibidas: ' . print_r($plazas, true));

            // Filtrar plazas que no están vacías (null)
            $plazasValidas = array_filter($plazas, function($plaza) {
                return $plaza !== null;
            });

            //Debug plazas validas
            error_log('Plazas filtradas: ' . print_r($plazasValidas, true));

            $cantidadPlazas = count($plazasValidas);

            if (in_array($tipoVehiculo, ['moto', 'auto', 'camioneta']) && $cantidadPlazas !== 1) {
                $response['errors'][] = "Debe seleccionar una plaza de parking.";
            } elseif (in_array($tipoVehiculo, ['camion', 'utilitario']) && $cantidadPlazas !== 2) {
                $response['errors'][] = "Debe seleccionar dos plazas de parking.";
            } else {
                // Instanciar una reserva de Parking
                $this->parking = new Parking($largo_plazo, $tipo_plaza, null, $precio, $fecha_inicio, $fecha_final);

                // Inicializar una conexión PDO como cliente
                $this->parking->setDBConnection("cliente");
                $this->parking->comenzarTransaccion();

                try {
                    if (!$this->parking->reservar($matricula)) {
                        throw new Exception("Fallo al reservar el servicio");
                    }
                    foreach ($plazasValidas as $plaza) {
                        if (!$this->parking->apartarPlaza($plaza)) {
                            throw new Exception("Fallo al apartar la plaza");
                        }
                    }
                    // Confirmar la transacción realizada
                    $this->parking->confirmarTransaccion();
                    $response['success'] = true;

                    // Guardar la reserva en la sesión
                    $_SESSION['reserva'] = $this->parking;
                    $_SESSION['plazasReservadas'] = strtoupper(implode(', ', $plazasValidas));
                    $_SESSION['servicio'] = 'parking';
                    $_SESSION['matricula'] = $_SESSION['parking']['matricula'];

                    // Redireccionar al usuario a la página de confirmación de reserva
                    return $this->redirectToRoute('parkingConfirmation');
                } catch (Exception $e) {
                    $this->parking->deshacerTransaccion();
                    $response['errors'][] = "Error procesando la reserva: " . $e->getMessage();
                } finally {
                    // Desconectar de la base de datos
                    $this->parking->cerrarDBConnection();
                }
            }
            return $this->render('client/eleccionPlazaParking.html.twig', [
                'response' => $response,
                'tipoVehiculo' => $_SESSION['parking']['tipoVehiculo'] ?? null,
                'plazasLibres' => $_SESSION['plazasLibres'] ?? null,
                'parking_data' => $_SESSION['parking'] ?? null
            ]);
        }
    }

    function parkingConfirmation(): Response{
        $sessionData = [
            'email' => $_SESSION['email'],
            'nombre' => $_SESSION['nombre'],
            'apellido' => $_SESSION['apellido'],
            'servicio' => $_SESSION['servicio'],
            'reserva' => isset($_SESSION['reserva']) ? [
                'id' => $_SESSION['reserva']->getId(),
                'matricula' => $_SESSION['matricula'],
                'precio' => $_SESSION['reserva']->getPrecio(),
                'fecha_inicio' => $_SESSION['reserva']->getFecha_inicio(),
                'fecha_final' => $_SESSION['reserva']->getFecha_final(),
                'tipo_plaza' => $_SESSION['reserva']->getTipo_plaza(),
                'largo_plazo' => ($_SESSION['reserva']->getLargo_plazo()==true) ? "Largo Plazo" : "Regular",
                'plazas' => $_SESSION['plazasReservadas']
            ] : null,
        ];
        // Imprimir los datos en pagina de confirmacion
        return $this->render('client/reservaConfirmacion.html.twig', ['sessionData' => $sessionData]);
    }

    private function obtenerFechaFinal($inicio, $tipoReserva){
        $formato = 'Y-m-d\TH:i';
        $dt = DateTime::createFromFormat($formato, $inicio);

        if ($dt){
            switch($tipoReserva){
                case 'semanal':
                    $dt->modify("+7 day");
                    return $dt->format($formato); // Devolver la fecha modificada
                    break;
                case 'quincenal':
                    $dt->modify("+15 day");
                    return $dt->format($formato);
                    break;
                case 'mensual':
                    $dt->modify("+1 month");
                    return $dt->format($formato);
                    break;
                case 'anual':
                    $dt->modify("+1 year");
                    return $dt->format($formato);
                    break;
                default:
                    break;
            }
        }
        
        return false;
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

    private function cargarPlazasPorTipo($pathPlazasJson){
        if(!file_exists($pathPlazasJson)){
            throw $this->createNotFoundException('El archivo de plazas por tipo no existe.');
        }

        $contenidoJson = file_get_contents($pathPlazasJson);
        $plazas = json_decode($contenidoJson, true);

        if ($plazas === null && json_last_error() !== JSON_ERROR_NONE){
            throw new Exception('Error al decodificar el JSON: ' . json_last_error_msg());
        } else {
            // Si el array no es vacío, settear variable global como la extraída del JSON; si no, settear como array vacío
            $this->plazasMotoSimple = isset($plazas['moto']['simple']) ? $plazas['moto']['simple'] : [];
            $this->plazasMotoLargoPlazo = isset($plazas['moto']['largo_plazo']) ? $plazas['moto']['largo_plazo'] : [];
            $this->plazasAutoSimple = isset($plazas['auto']['simple']) ? $plazas['auto']['simple'] : [];
            $this->plazasAutoLargoPlazo = isset($plazas['auto']['largo_plazo']) ? $plazas['auto']['largo_plazo'] : [];
        }
    }

}