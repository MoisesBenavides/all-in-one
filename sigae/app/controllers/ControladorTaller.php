<?php
require_once '../app/models/Taller.php';

class ControladorTaller{
    private $taller;

    public function __construct(){
        $this->taller = new Taller();
    }

    function doBookService(){
        $response=['success' => false, 'errors' => [], 'debug' => []];

        // Debug: Log all received data
        $response['debug']['received_data']=$_POST;

        // Validacion de campos vacios
        if (isset($_POST["fecha_inicio"], $_POST["servicio_id"], $_POST["fecha_reserva"]) && 
            !empty($_POST["cliente_id"]) &&!empty($_POST["servicio_id"]) &&!empty($_POST["fecha_reserva"])) {
            // Validacion de cliente existente
            
        }
    }
}