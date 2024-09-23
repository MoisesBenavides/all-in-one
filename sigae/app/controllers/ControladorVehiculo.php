<?php

require_once '../app/models/Vehiculo.php';
require_once '../app/models/TipoVehiculo.php';

class ControladorVehiculo{
    private $vehiculo;

    public function registrarYaVehiculo($matricula, $tipoVehiculo){
        if (Vehiculo::existeMatricula($matricula)) {
            return false; // False si la matrícula ya existe
        } else {
            $this->vehiculo = new Vehiculo($matricula, null, null, $tipoVehiculo, null);
            return $this->vehiculo->registrarYa();
        }
    
    }

    public function validarTipoVehiculo($tipoVehiculo){
        // Valida si el tipo de vehiculo a partir del enum TipoVehiculo.php
        return TipoVehiculo::tryFrom($tipoVehiculo) !== null;
    }

    public function validarMatricula($str){
        return preg_match("/^[a-zA-Z0-9]{4,8}$/", $str);
    }
}