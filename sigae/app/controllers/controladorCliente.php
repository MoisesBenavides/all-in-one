<?php

require_once '../app/models/Cliente.php';

class ControladorCliente{
    private $cliente;

    public function __construct(){
        $this->cliente=new Cliente();
    }

    function mostrarHome(){
        include '../app/views/client/homeCliente.html';
    }
}