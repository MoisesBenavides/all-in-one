<?php

require_once '../app/controllers/ControladorCliente.php';

$controladorCliente = new ControladorCliente();

$action = isset($_GET['action']) ? $_GET['action'] : 'login';

switch ($action) {
    case 'login':
        $controladorCliente->login();
        break;
    case 'doLogin':
        $controladorCliente->doLogin();
        break;
    case 'signup':
        $controladorCliente->signup();
        break;
    case 'doSignUp':
        $controladorCliente->doSignup();
        break;
    case 'forgotPassword':
        $controladorCliente->forgotPassword();
        break;
    case 'services':
        $controladorCliente->services();
        break;
    case 'home':
        $controladorCliente->home();
        break;
    default:
        $controladorCliente->login();
        break;
}