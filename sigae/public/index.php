<?php

require_once '../app/controllers/ControladorCliente.php';
require_once '../app/controllers/ControladorTaller.php';
require_once '../app/controllers/ControladorParking.php';

$controladorCliente = new ControladorCliente();
$controladorTaller = new ControladorTaller();
$controladorParking = new ControladorParking();

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
    case 'bookParkingSimple':
        $controladorParking->bookParkingSimple();
        break;
    case 'aioParkingBookLongTerm':
        $controladorCliente->parkingLongTerm();
        break;
    case 'bookParkingLongTerm':
        $controladorParking->bookParkingLongTerm();
        break;
    case 'parkingConfirmation':
        $controladorParking->parkingConfirmation();
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