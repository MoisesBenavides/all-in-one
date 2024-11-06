<?php

namespace Sigae\Controllers;
use Sigae\Models\Taller;
use Sigae\Models\Cliente;
use Sigae\Controllers\ControladorVehiculo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Exception;
use DateTime;
use DateTimeZone;
use InvalidArgumentException;

class ControladorTaller extends AbstractController{
    private const PATH_SERVICIOS_JSON = __DIR__ . '/../data/serviciosTaller.json';
    private const PATH_HORARIOS_JSON = __DIR__ . '/../data/horariosTaller.json';
    private $taller;
    private $horarios;
    private $serviciosDisp;
    private $controladorVehiculo;
    private $registrarYa;

    public function __construct(){
        $this->cargarServicios(self::PATH_SERVICIOS_JSON);
        $this->cargarHorarios(self::PATH_HORARIOS_JSON);
        $this->controladorVehiculo = new ControladorVehiculo();
    }

    function bookService(): Response{
        $misVehiculos = Cliente::cargarMisVehiculos($_SESSION['id']);

        return $this->render('client/reservarServicio.html.twig', [
           'misVehiculos' => $misVehiculos
        ]);
    }
    
    function doBookService(): Response|RedirectResponse{
        $response = ['success' => false, 'errors' => [], 'debug' => []];
    
        // Debug: Log all received data
        $response['debug']['received_data'] = $_POST;
    
        // Validación de campos vacíos
        if (isset($_POST["fecha_inicio"], $_POST["categoriaServicio"], $_POST["tipoServicio"], $_POST["tipoVehiculo"]) 
            && (!empty($_POST["matriculaYa"]) || !empty($_POST["matricula"]))
            && !empty($_POST["fecha_inicio"]) && !empty($_POST["categoriaServicio"]) && !empty($_POST["tipoServicio"]) && !empty($_POST["tipoVehiculo"])) {
    
            $fecha_inicio = $_POST["fecha_inicio"];
            $categoriaServicio = $_POST["categoriaServicio"];
            $tipoServicio = $_POST["tipoServicio"];
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
    
                // Debug: Datos procesados
                $response['debug']['processed_data'] = [
                    'fecha_inicio' => $fecha_inicio,
                    'categoriaServicio' => $categoriaServicio,
                    'tipoServicio' => $tipoServicio,
                    'tipoVehiculo' => $tipoVehiculo,
                    'matricula' => $matricula
                ];
    
                // Validaciones
                if (!$this->validarFecha($fecha_inicio)) {
                    $response['errors'][] = "Por favor, ingrese una fecha válida.";
                } elseif (!$this->validarTipoServicio($tipoServicio)) {
                    $response['errors'][] = "El servicio seleccionado no está disponible.";
                } elseif (!$this->controladorVehiculo->validarTipoVehiculo($tipoVehiculo)) {
                    $response['errors'][] = "El tipo de vehículo seleccionado no está disponible.";
                } elseif (!$this->controladorVehiculo->validarMatricula($matricula)) {
                    $response['errors'][] = "Por favor, ingrese una matrícula válida.";
                } else {
                    // Obtener datos del tipo de servicio ingresado
                    $descripcion = $this->serviciosDisp[$tipoServicio]['descripcion'];
                    $tiempo_estimado = $this->serviciosDisp[$tipoServicio]['tiempo_estimado'];
                    $precio = $this->serviciosDisp[$tipoServicio]['precio'];
    
                    $fecha_final = $this->estimarFechaFinal($fecha_inicio, $tiempo_estimado);
    
                    // Parsear fechas
                    $fecha_inicioParsed = str_replace('T', ' ', $fecha_inicio) . ':00';
                    $fecha_finalParsed = str_replace('T', ' ', $fecha_final) . ':00';
    
                    $id_cliente = $_SESSION['id'];
    
                    $this->taller = new Taller($tipoServicio, $descripcion, null, $tiempo_estimado, null, $precio, $fecha_inicioParsed, $fecha_finalParsed);

                    // Inicializar una conexión PDO como cliente
                    $this->taller->setDBConnection("cliente");
                    $this->taller->comenzarTransaccion();

                    try{
                        if ($this->registrarYa && !$this->controladorVehiculo->registrarYaVehiculo($matricula, $tipoVehiculo, $id_cliente)){
                            throw new Exception($e->getMessage());
                        } elseif (!$this->taller->reservar($matricula)){
                            throw new Exception("Fallo al reservar el servicio");
                        } else{
                            $this->taller->confirmarTransaccion();
                            $response['success'] = true;
    
                            // TODO: Enviar correo de confirmación
    
                            // Guardar la reserva en la sesión
                            $_SESSION['reserva'] = $this->taller;
                            $_SESSION['servicio'] = 'taller';
                            $_SESSION['matricula'] = $matricula;
    
                            // Redireccionar al usuario a la página de confirmación de reserva
                            return $this->redirectToRoute('serviceConfirmation');
                        }

                    } catch(Exception $e){
                        $this->taller->deshacerTransaccion();
                        $response['errors'][] = $e->getMessage();
                    } finally {
                        $this->taller->cerrarDBConnection();
                    }
                }
            }
        } else {
            $response['errors'][] = "Debe llenar todos los campos.";
            // Debug: Log which fields are missing
            $response['debug']['missing_fields'] = array_diff(
                ['fecha_inicio', 'categoriaServicio', 'tipoServicio', 'tipoVehiculo', 'matricula'],
                array_keys($_POST)
            );
        }
        $misVehiculos = Cliente::cargarMisVehiculos($_SESSION['id']);
        return $this->render('client/reservarServicio.html.twig', [
            'response' => $response,
            'misVehiculos' => $misVehiculos
        ]); // Pasa la respuesta a la vista
    }

    function serviceConfirmation(): Response{
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
                'tipo' => $_SESSION['reserva']->getTipo(),
                'descripcion' => $_SESSION['reserva']->getDescripcion(),
                'tiempo_estimado' => $_SESSION['reserva']->getTiempo_estimado(),
            ] : null,
        ];
        // Imprimir los datos en pagina de confirmacion
        return $this->render('client/reservaConfirmacion.html.twig', ['sessionData' => $sessionData]);
    }
    
    public function getServicesSchedule(Request $request): JsonResponse {
        try {
            // Obtener y validar el día seleccionado desde la solicitud
            $diaSelec = $request->query->get('date');
            if (!$diaSelec) {
                throw new InvalidArgumentException('El día de reserva es requerido.');
            }
    
            $dia = new DateTime($diaSelec);
            $fijos = $this->horarios;
    
            // Verificar que los horarios fijos existen
            if (!$fijos) {
                throw new Exception("No se pudo cargar los horarios del taller.");
            }
    
            // Obtener lapsos ocupados para el día seleccionado desde la base de datos
            $ocupados = Taller::obtenerLapsosOcupados('cliente', $dia);
    
            // Inicializar array de respuesta
            $horariosTallerDia = [];
    
            foreach ($fijos as $lapso => $detalles) {
                // Convertir inicio y fin de cada lapso a DateTime para la comparación
                $inicioLapso = new DateTime($dia->format('Y-m-d') . ' ' . $detalles['inicio']);
                $finLapso = new DateTime($dia->format('Y-m-d') . ' ' . $detalles['fin']);
    
                // Establecer el estado inicial de `ocupado` como false
                $ocupado = false;
    
                // Comparar con cada horario ocupado
                foreach ($ocupados as $oc) {
                    if (isset($oc['hora_inicio'], $oc['hora_fin'])) {  // Validar existencia de claves
                        $inicioOcupado = new DateTime($oc['hora_inicio']);
                        $finOcupado = new DateTime($oc['hora_fin']);
    
                        // Revisar si el horario actual del lapso está ocupado
                        if ($inicioLapso < $finOcupado && $finLapso > $inicioOcupado) {
                            $ocupado = true;
                            break; // No es necesario verificar más si ya está ocupado
                        }
                    } else {
                        error_log("Error: horario ocupado no tiene 'hora_inicio' o 'hora_fin'");
                    }
                }
    
                // Agregar los detalles de cada lapso al arreglo final, incluyendo `ocupado`
                $horariosTallerDia[$lapso] = [
                    'ocupado' => $ocupado,
                    'inicio' => $detalles['inicio'],
                    'fin' => $detalles['fin']
                ];
            }
    
            // Log para verificar la estructura de los horarios antes de devolverlos
            error_log("Horarios Taller Procesados: " . print_r($horariosTallerDia, true));
    
            // Responder con los horarios del taller para el día seleccionado
            return new JsonResponse([
                'success' => true,
                'horariosTaller' => $horariosTallerDia,
            ]);
    
        } catch (Exception $e) {
            // En caso de error, devolver una respuesta con el mensaje
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
    
    public function getServicesSchedule1(Request $request): JsonResponse{
        try {
            // Obtener el día seleccionado desde el cliente
            $diaSelec = $request->query->get('date');
            if (!$diaSelec) {
                throw new InvalidArgumentException('El día de reserva es requerido.');
            }

            $dia = new DateTime($diaSelec);

            $fijos = $this->horarios;
            
            // Carga el arreglo del archivo json con los horarios fijos de lapsos
            if (!$fijos) {
                throw new Exception("No se pudo cargar los horarios del taller.");
            }

            // Obtener lapsos ocupados de la base de datos para el día seleccionado
            $ocupados = [];
            try{
                // Asigna como rol para conexion, cliente
                // TODO: Implementar asignación dinámica por rol de funcionario
                $rol='cliente';
                $ocupados = Taller::obtenerLapsosOcupados($rol, $dia);
            } catch(Exception $e){
                error_log($e->getMessage());
                throw $e;
            }
            
            // Procesar horarios del json, marcando los ocupados
            $horariosTallerDia = [];
            foreach ($fijos as $lapso => $detalles) {
                // Convertir el inicio y fin del lapso a DateTime para comparar
                $inicioLapso = new DateTime($dia->format('Y-m-d') . ' ' . $detalles['inicio']);
                $finLapso = new DateTime($dia->format('Y-m-d') . ' ' . $detalles['fin']);

                // Revisa si el lapso está ocupado
                $ocupado = false;
                foreach ($ocupados as $oc) {
                    $inicioOcupado = new DateTime($oc['hora_inicio']);
                    $finOcupado = new DateTime($oc['hora_fin']);

                    // Comparar si el horario de este lapso está ocupado
                    if ($inicioLapso < $finOcupado && $finLapso > $inicioOcupado) {
                        $ocupado = true;
                        break;
                    }
                }

                // Añadir cada lapso con el estado ocupado o no ocupado
                $horariosTallerDia[$lapso] = [
                    'ocupado' => $ocupado,
                    'inicio' => $detalles['inicio'],
                    'fin' => $detalles['fin']
                ];
            }

            // Obtener hora actual en Montevideo
            $uruguayTimezone = new DateTimeZone('America/Montevideo');
            $dtActual = new DateTime('now', $uruguayTimezone);

            // Formatear la fecha
            $horaActual = $dtActual->format('Y-m-d H:i:s');

            error_log($horaActual);

            error_log("Horarios Taller: " . print_r($horariosTallerDia, true));

            return new JsonResponse([
                'success' => true,
                'horariosTaller' => $horariosTallerDia,
                'horaActual' => $horaActual
            ]);

        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    private function estimarFechaFinal($fecha, $minutos) {
        $formato = 'Y-m-d\TH:i';
        $dt = DateTime::createFromFormat($formato, $fecha);
        
        // Verificamos si la fecha es válida
        if ($dt) {
            // Sumar los minutos
            $dt->modify("+{$minutos} minutes");
            return $dt->format($formato); // Devolver la fecha modificada
        }
        
        return false;
    }

    private function validarFecha($fecha){
        $formato = 'Y-m-d\TH:i'; // Formato de datetime-local (ej. 2024-10-22T19:30)
    
        // Intentamos crear un objeto DateTime con string de la fecha
        $dt = DateTime::createFromFormat($formato, $fecha);
        
        // Validar si el formato es válido
        return $dt && $dt->format($formato) === $fecha;
    }

    private function validarTipoServicio($tipoServicio){
        // Validar si el código seleccionado existe en la lista de servicios disponibles
        return isset($this->serviciosDisp[$tipoServicio]);
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

    private function cargarServicios($pathServiciosJson){

        // Verificar si el archivo existe
        if (!file_exists($pathServiciosJson)) {
            throw $this->createNotFoundException('El archivo de precios no existe.');
        }

        $contenidoJson = file_get_contents($pathServiciosJson);
        $servicios = json_decode($contenidoJson, true);

        // Verificar si hubo error en la decodificación del JSON
        if ($servicios === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error al decodificar el JSON: ' . json_last_error_msg());
        } else
            $this->serviciosDisp = $servicios;
    }

    private function cargarHorarios($pathHorariosJson) {
        // Verificar si el archivo existe
        if (!file_exists($pathHorariosJson)) {
            throw $this->createNotFoundException('El archivo de horarios no existe.');
        }
    
        $contenidoJson = file_get_contents($pathHorariosJson);
        $horarios = json_decode($contenidoJson, true);
    
        // Verificar si json_decode falló
        if ($horarios === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error al decodificar el JSON de horarios: ' . json_last_error_msg());
        } else    
            $this->horarios = $horarios;
    }
}
