<?php
require_once '../app/models/Taller.php';
require_once '../app/models/Vehiculo.php';
require_once '../app/models/TipoVehiculo.php';

class ControladorTaller{
    private $taller;
    private $vehiculo;
    private $serviciosDisp;

    public function __construct(){
        $serviciosJson = '../app/data/serviciosTaller.json';

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
            $tipoServicio = $_POST["tipoServicio"];
            $tipoVehiculo = $_POST["tipoVehiculo"];
            $matricula = $_POST["matricula"];
            
            // Debug: Log processed data
            $response['debug']['processed_data'] = [
                'fecha_inicio' => $fecha_inicio,
                'categoriaServicio' => $categoriaServicio,
                'nombreServicio' => $nombreServicio,
                'tipoVehiculo' => $tipoVehiculo,
                'matricula' => $matricula
            ];

            if (!$this->validarFecha($fecha_inicio)){
                $response['errors'][] = "Por favor, ingrese una fecha válida.";
            } elseif(!$this->validarTipoServicio($tipoServicio)){
                $response['errors'][] = "El servicio seleccionado no está disponible.";
            } elseif(TipoVehiculo::tryFrom($tipoVehiculo) == null){
                $response['errors'][] = "El tipo de vehículo seleccionado no está disponible.";
            } elseif(!$this->validarMatricula($matricula)){ // IMPLEMENTAR y validar tipo de vehiculo
                $response['errors'][] = "Por favor, ingrese una matrícula válida.";
            } else {
                // IMPLEMENTAR
                $fecha_final=estimarFechaFinal($fecha_inicio);
                // IMPLEMENTAR
                $descripcion=obtenerDescripcion($tipo_servicio);
                // IMPLEMENTAR
                $tiempo_estimado=obtenerTiempoEstimado($tipo_servicio);
                // IMPLEMENTAR
                $precio=estimarPrecio($tipoServicio);
                $this->vehiculo = new Vehiculo();
                $this->taller = new Taller($tipoServicio, $descripcion, null, $tiempo_estimado, null, $precio, $fecha_inicio, $fecha_final);
                if ($this->vehiculo->guardarVehiculo() && $this->taller->reservarServicio($matricula)){
                    $response['success'] = true;
                    //$response['message'] = "Reserva realizada con éxito.";

                    // TODO: Enviar correo de confirmación

                    // Guardar la reserva en la sesión
                    $_SESSION['reserva'] = $this->taller; 

                    $idReserva = $this->taller->getId();

                    // Redireccionar al usuario a la página de confirmación de reserva
                    header('Location: index.php?action=serviceConfirmation'.'reserva'.($idReserva));
                    exit;
                } else {
                    $response['errors'][] = "Error al reservar servicio";
                }
            }


        } else {
            $response['errors'][] = "Debe llenar todos los campos.";
            // Debug: Log which fields are missing
            $response['debug']['missing_fields'] = array_diff(
                ['fecha_inicio', 'categoriaServicio', 'nombreServicio', 'tipoVehiculo', 'matricula'],
                array_keys($_POST)
            );
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    private function validarFecha($fecha){
        $formato = 'Y-m-d\TH:i'; // Formato de datetime-local (ej. 2024-10-22T19:30)
    
        // Intentamos crear un objeto DateTime con string de la fecha
        $dateTime = DateTime::createFromFormat($formato, $fecha);
        
        // Validar si el formato es válido
        return $dateTime && $dateTime->format($formato) === $fecha;
    }

    private function validarTipoServicio($tipoServicio){
        // Validar si el código seleccionado existe en la lista de servicios disponibles
        return isset($this->serviciosDisp[$tipoServicio]);
    }

    private function validarMatricula($matricula){
        return true;
    }

}