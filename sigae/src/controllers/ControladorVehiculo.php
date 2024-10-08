<?php

namespace Sigae\Controllers;
use Sigae\Models\Vehiculo;
use Sigae\Models\TipoVehiculo;

class ControladorVehiculo{
    private $vehiculo;

    public function registrarYaVehiculo($matricula, $tipoVehiculo, $id_cliente){
        if (Vehiculo::existeMatricula($matricula)) {
            return false; // False si la matrícula ya existe
        } else {
            $this->vehiculo = new Vehiculo($matricula, null, null, $tipoVehiculo, null);
            return $this->vehiculo->registrarYa($id_cliente);
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