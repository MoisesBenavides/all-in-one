<?php
require_once '../app/models/Taller.php';
require_once '../app/controllers/ControladorVehiculo.php';

class ControladorTaller{
    private $taller;
    private $serviciosDisp;
    private $controladorVehiculo;

    public function __construct(){
        $serviciosJson = '../app/data/serviciosTaller.json';
        $this->controladorVehiculo = new ControladorVehiculo();

        // Verificar si el archivo existe
        if (file_exists($serviciosJson)) {
            $contenidoJson = file_get_contents($serviciosJson);
            $this->serviciosDisp = json_decode($contenidoJson, true);

            // Verificamos si hubo error en la decodificación del JSON
            if ($this->serviciosDisp === null && json_last_error() !== JSON_ERROR_NONE) {
                die("Error al decodificar el archivo JSON: " . json_last_error_msg());
            }
        } else {
            die("El archivo JSON de servicios no existe.");
        }
    }
    function doBookService(){
        session_start();
        $response=['success' => false, 'errors' => [], 'debug' => []];

        // Debug: Log all received data
        $response['debug']['received_data']=$_POST;

        // Validacion de campos vacios
        if (isset($_POST["fecha_inicio"], $_POST["categoriaServicio"], $_POST["tipoServicio"], $_POST["tipoVehiculo"], $_POST["matricula"]) && 
            !empty($_POST["fecha_inicio"]) && !empty($_POST["categoriaServicio"]) && !empty($_POST["tipoServicio"]) 
            && !empty($_POST["tipoVehiculo"]) && !empty($_POST["matricula"]) ) {
                
            $fecha_inicio = $_POST["fecha_inicio"];
            $categoriaServicio = $_POST["categoriaServicio"];
            $tipoServicio = $_POST["tipoServicio"];
            $tipoVehiculo = strtolower($_POST["tipoVehiculo"]);
            $matricula = strtoupper($_POST["matricula"]);
            // Debug: Log processed data
            $response['debug']['processed_data'] = [
                'fecha_inicio' => $fecha_inicio,
                'categoriaServicio' => $categoriaServicio,
                'tipoServicio' => $tipoServicio,
                'tipoVehiculo' => $tipoVehiculo,
                'matricula' => $matricula
            ];

            if (!$this->validarFecha($fecha_inicio)){
                $response['errors'][] = "Por favor, ingrese una fecha válida.";
            } elseif(!$this->validarTipoServicio($tipoServicio)){
                $response['errors'][] = "El servicio seleccionado no está disponible.";
            } elseif(!$this->controladorVehiculo->validarTipoVehiculo($tipoVehiculo)){
                $response['errors'][] = "El tipo de vehículo seleccionado no está disponible.";
            } elseif(!$this->controladorVehiculo->validarMatricula($matricula)){
                $response['errors'][] = "Por favor, ingrese una matrícula válida.";
            } else {
                // Obtener datos del tipo de servicio ingresado
                $descripcion = $this->serviciosDisp[$tipoServicio]['descripcion'];
                $tiempo_estimado = $this->serviciosDisp[$tipoServicio]['tiempo_estimado'];
                $precio = $this->serviciosDisp[$tipoServicio]['precio'];

                $fecha_final=$this->estimarFechaFinal($fecha_inicio, $tiempo_estimado);
                
                // Parsear fechas
                $fecha_inicioParsed = str_replace('T', ' ', $fecha_inicio) . ':00';
                $fecha_finalParsed = str_replace('T', ' ', $fecha_final) . ':00';

                $this->taller = new Taller($tipoServicio, $descripcion, null, $tiempo_estimado, null, $precio, $fecha_inicioParsed, $fecha_finalParsed);
                if (!$this->controladorVehiculo->registrarYaVehiculo($matricula, $tipoVehiculo)){
                    $response['errors'][] = "Ya existe un vehiculo con la matricula ingresada.";
                } elseif (!$this->taller->reservarServicio($matricula)){
                    $response['errors'][] = "Error al reservar servicio.";
                } else {
                    $response['success'] = true;
                    //$response['message'] = "Reserva realizada con éxito.";

                    // TODO: Enviar correo de confirmación

                    // Guardar la reserva en la sesión
                    

                    // $idReserva = $this->taller->getId();
                    $_SESSION['reserva'] = $this->taller; 

                    // Redireccionar al usuario a la página de confirmación de reserva
                    header('Location: index.php?action=serviceConfirmation');
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
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    function serviceConfirmation(){
        session_start();
        error_log($_SESSION['email']. " reservó un servicio de taller");
        error_log(print_r($_SESSION, true));
        include '../app/views/client/reservaConfirmacion.html';
    }

    function estimarFechaFinal($fecha, $minutos) {
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

}