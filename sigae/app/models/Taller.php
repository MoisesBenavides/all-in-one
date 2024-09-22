<?php

require_once '../app/db_config/db_conn.php';
require_once 'Servicio.php';

class Taller extends Servicio{
    private $tipo;
    private $descripcion;
    private $diagnostico;
    private $tiempo_estimado;

    public function __construct($tipo, $descripcion, $diagnostico, $tiempo_estimado, $id, $precio, $fecha_inicio, $fecha_final, EstadoServicio $estado){
        parent::__construct($id, $precio, $fecha_inicio, $fecha_final, $estado);
        $this->tipo = $tipo;
        $this->descripcion = $descripcion;
        $this->diagnostico = $diagnostico;
        $this->tiempo_estimado = $tiempo_estimado;
    }

    public function getTipo(){
        return $this->tipo;
    }

    public function setTipo($tipo){
        $this->tipo = $tipo;
    }

    public function getDescripcion(){
        return $this->descripcion;
    }

    public function setDescripcion($descripcion){
        $this->descripcion = $descripcion;
    }

    public function getDiagnostico(){
        return $this->diagnostico;
    }

    public function setDiagnostico($diagnostico){
        $this->diagnostico = $diagnostico;
    }

    public function getTiempo_estimado(){
        return $this->tiempo_estimado;
    }

    public function setTiempo_estimado($tiempo_estimado){
        $this->tiempo_estimado = $tiempo_estimado;
    }
}