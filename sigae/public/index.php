<?php

require_once '../app/controllers/ControladorCliente.php';

$controladorCliente = new ControladorCliente();

$action = isset($_GET['action']) ? $_GET['action'] : 'home';

switch ($action) {
    default: 
        $controladorCliente->mostrarHome();
        break;
}