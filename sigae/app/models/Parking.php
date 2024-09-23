<?php

require_once '../app/db_config/db_conn.php';
require_once 'TipoPlazaParking.php';
require_once 'Servicio.php';

class Parking extends Servicio{
    private $largo_plazo;
    private TipoPlazaParking $tipo_plaza;

    public function __construct($largo_plazo, $tipo_plaza, $id, $precio, $fecha_inicio, $fecha_final){
        parent::__construct($id, $precio, $fecha_inicio, $fecha_final);
        $this->largo_plazo = $largo_plazo;
        $this->tipo_plaza = $tipo_plaza;
    }

    public function getLargo_plazo(){
        return $this->largo_plazo;
    }

    public function setLargo_plazo($largo_plazo){
        $this->largo_plazo = $largo_plazo;

        return $this;
    }

    public function cambiarServicio(){
    }

    public function cancelarServicio(){
    }

    public function getServicios(){
    }
}