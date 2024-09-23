<?php

require_once '../app/controllers/ControladorCliente.php';
require_once '../app/controllers/ControladorTaller.php';

$controladorCliente = new ControladorCliente();
$controladorTaller = new ControladorTaller();

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
    case 'doSignup':
        $controladorCliente->doSignup();
        break;
    case 'forgotPassword':
        $controladorCliente->forgotPassword();
        break;
    case 'services':
        $controladorCliente->services();
        break;
    case 'bookService':
        $controladorCliente->bookService();
        break;
    case 'doBookService':
        $controladorTaller->doBookService();
        break;
    case 'serviceConfirmation':
        $controladorTaller->serviceConfirmation();
        break;
    case 'aioParking':
        $controladorCliente->parking();
        break;
    case 'aioParkingBookSimple':
        $controladorCliente->parkingSimple();
        break;
    case 'aioParkingBookLongTerm':
        $controladorCliente->parkingLongTerm();
        break;
    case 'myUser':
        $controladorCliente->myUser();
        break;
    case 'home':
        $controladorCliente->home();
        break;
    default:
        $controladorCliente->login();
        break;
}