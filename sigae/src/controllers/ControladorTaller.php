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
    }

    public function getServiciosDisp(){
        return $this->serviciosDisp;
    }

    public function getHorarios(){
        return $this->horarios;
    }

    function bookService(): Response{
        $misVehiculos = Cliente::cargarMisVehiculos($_SESSION['id']);

        return $this->render('client/reservarServicio.html.twig', [
           'misVehiculos' => $misVehiculos
        ]);
    }
    
    function doBookService(): Response|RedirectResponse{
        $response = ['success' => false, 'errors' => []];
    
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

                $this->controladorVehiculo = new ControladorVehiculo();

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
            'nombre' => $_SESSION['nombre'],
            'apellido' => $_SESSION['apellido'],
            'servicio' => $_SESSION['servicio'], 
            'reserva' => isset($_SESSION['reserva']) ? [
                'id' => $_SESSION['reserva']->getId(),
                'matricula' => $_SESSION['matricula'],
                'precio' => $_SESSION['reserva']->getPrecio(),
                'fecha_inicio' => $_SESSION['reserva']->getFecha_inicio(),
                'fecha_final' => $_SESSION['reserva']->getFecha_final(),
                'tipo' => $_SESSION['reserva']->getTipo(),
                'descripcion' => $_SESSION['reserva']->getDescripcion()
            ] : null,
        ];
        // Imprimir los datos en pagina de confirmacion
        return $this->render('client/reservaConfirmacion.html.twig', ['sessionData' => $sessionData]);
    }
    
    public function getServicesSchedule(Request $request): JsonResponse {
        try {
            $diaSelec = $request->query->get('date');
            if (!$diaSelec) {
                throw new InvalidArgumentException('El día de reserva es requerido.');
            }
    
            // Formatea la fecha seleccionada
            $dia = new DateTime($diaSelec);
            $fijos = $this->horarios;  // JSON de horarios fijos
    
            if (!$fijos) {
                throw new Exception("No se pudo cargar los horarios del taller.");
            }
    
            // Obtener lapsos ocupados de la base de datos para el día seleccionado
            $ocupados = Taller::obtenerLapsosOcupados('cliente', $dia);
    
            // Crear estructura de lapsos para la respuesta, marcando los ocupados
            $horariosTallerDia = [];
            foreach ($fijos as $lapso => $detalles) {
                // Convertir el inicio y fin del lapso a DateTime para comparar
                $inicioLapso = new DateTime($dia->format('Y-m-d') . ' ' . $detalles['inicio']);
                $finLapso = new DateTime($dia->format('Y-m-d') . ' ' . $detalles['fin']);
                
                // Establece el estado como falso de inicio
                $ocupado = false;
                // Revisa si el lapso está ocupado
                foreach ($ocupados as $oc) {
                    $inicioOcupado = new DateTime($oc['fecha_inicio']);
                    $finOcupado = new DateTime($oc['fecha_final']);
    
                    // Compara si el lapso del horario está ocupado
                    if ($inicioLapso <= $finOcupado && $finLapso >= $inicioOcupado) {
                        $ocupado = true;
                        break;
                    }
                }
    
                // Almacena el lapso con el estado ocupado o libre
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

            // Loguear $horariosTallerDia antes de la respuesta
            error_log('Horarios: ' . print_r($horariosTallerDia, true));

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
