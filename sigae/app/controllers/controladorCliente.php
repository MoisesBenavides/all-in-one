<?php
require_once '../app/models/Cliente.php';

class ControladorCliente{
    private $cliente;

    public function __construct(){
        $this->cliente=new Cliente();
    }
    function login(){
        include '../app/views/account/login.html';
    }
    function doLogin(){
        $response=['success' => false, 'errors' => [], 'debug' => []];

        // Debug: Log all received data
        $response['debug']['received_data']=$_POST;

        // Validacion de campos vacios
        if (isset($_POST["email"], $_POST["contrasena"]) && 
            !empty($_POST["email"]) && !empty($_POST["contrasena"])) {

            $email = $_POST["email"];
            $contrasena = $_POST["contrasena"];

            // Debug: Log processed data
            $response['debug']['processed_data'] = [
                'email' => $email,
                'contrasena' => 'REDACTED',
            ];

            // Validar email
            if (!$this->validarEmail($email, 63)) {
                $response['errors'][] = "Por favor, ingrese un correo electrónico válido.";
            } elseif (!$this->validarContrasena($contrasena, 6, 60)) {
                $response['errors'][] = "La contraseña debe tener entre 6 y 60 caracteres.";
            } else {
                if ($this->cliente->iniciarCliente($email, $contrasena)) {
                    $response['success'] = true;
                    
                    // Iniciar sesión del cliente
                    session_start();
                    $_SESSION['logged']= true;
                    $_SESSION['id']=$this->cliente->getId();
                    $_SESSION['ci']=$this->cliente->getCi();
                    $_SESSION['email']=$this->cliente->getEmail();
                    $_SESSION['nombre']=$this->cliente->getNombre();
                    $_SESSION['apellido']=$this->cliente->getApellido();
                    $_SESSION['telefono']=$this->cliente->getTelefono();

                    // Redirigir a la home page
                    header('Location: index.php?action=home');
                } else {
                    $response['errors'][] = "Error al iniciar sesión.";
                }
            }
        } else {
            $response['errors'][] = "Debe llenar todos los campos.";
            // Debug: Log which fields are missing
            $response['debug']['missing_fields'] = array_diff(
                ['email', 'contrasena'],
                array_keys($_POST)
            );
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    function signup(){
        include '../app/views/account/signup.html';
    }
    function doSignup(){
        $response=['success' => false, 'errors' => [], 'debug' => []];

        // Debug: Log all received data
        $response['debug']['received_data']=$_POST;

        // Validacion de campos vacios
        if (isset($_POST["email"], $_POST["nombre"], $_POST["apellido"], $_POST["contrasena"], $_POST["repContrasena"]) && 
            !empty($_POST["email"]) && !empty($_POST["nombre"]) && !empty($_POST["apellido"]) && !empty($_POST["contrasena"]) && !empty($_POST["repContrasena"])) {

            $email = $_POST["email"];
            $nombre = $_POST["nombre"];
            $apellido = $_POST["apellido"];
            $contrasena = $_POST["contrasena"];
            $repContrasena = $_POST["repContrasena"];

            // Debug: Log processed data
            $response['debug']['processed_data'] = [
                'email' => $email,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'contrasena' => 'REDACTED',
                'repContrasena' => 'REDACTED'
            ];

            if (!$this->validarEmail($email, 63)) {
                $response['errors'][] = "Por favor, ingrese un correo electrónico válido.";

            } elseif (!$this->validarNombreApellido($nombre, 23) || !$this->validarNombreApellido($apellido, 23)) {
                $response['errors'][] = "Por favor, ingrese un nombre o apellido válido.";

            } elseif (!$this->validarContrasena($contrasena, 6, 60)) {
                $response['errors'][] = "Use un mínimo de 6 caracteres con mayúsculas, minúsculas y números.";

            } elseif ($contrasena !== $repContrasena) {
                $response['errors'][] = "Las contraseñas no coinciden.";

            } elseif($this->cliente->existeEmail($email)) {
                $response['errors'][]= "Ya existe un usuario con el correo ingresado.";

            } elseif(!$this->cliente->guardarCliente(null, $email, $contrasena, $nombre, $apellido, null)){
                $response['errors'][] = "Error al registrarse.";

            } else {
                $response['success'] = true;
                // Redirigir al login
                header('Location: index.php?action=login');
            }
            
        } else {
            $response['errors'][] = "Debe llenar todos los campos.";
            // Debug: Log which fields are missing
            $response['debug']['missing_fields'] = array_diff(
                ['email', 'nombre', 'apellido', 'contrasena', 'repContrasena'],
                array_keys($_POST)
            );
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    function forgotPassword(){
        include '../app/views/account/forgotPassword.html';
    }
    function services(){
        session_start();
        error_log($_SESSION['email']. " abrió la página de servicios");
        error_log(print_r($_SESSION, true));
        include '../app/views/client/serviciosMecanicos.html';
    }
    function bookService(){
        session_start();
        error_log($_SESSION['email']. " abrió la página de reserva de servicios");
        error_log(print_r($_SESSION, true));
        include '../app/views/client/reservarServicio.html';
    }
    
    function parking(){
        include '../app/views/client/parking.html';
    }

    function myUser(){
        include '../app/views/client/miUsuario.html';
    }
    function home(){
        session_start();
        error_log($_SESSION['email']. " abrió la página home");
        error_log(print_r($_SESSION, true));
        include '../app/views/client/homeCliente.html';
    }
    private function validarContrasena($str, $min, $max) {
        /* Verifica si la contraseña contiene mayusculas, minusculas y numeros
        y si la extension de la cadena se ncuentra en el rango especificado por las variables $min y $max. */ 
        return ((preg_match('/[A-Z]/', $str) && preg_match('/[a-z]/', $str) && preg_match('/[0-9]/', $str) 
                && strlen($str) <= $max && strlen($str) >= $min));

    }
    private function validarEmail($str, $max) {
        /* Verifica si la cadena $str cumple con ciertos criterios de caracteres y contiene un dominio de correo valido
        y si la extension de la cadena es menor o igual al maximo especificado por la variable $max. */ 
        return (preg_match("/^[a-zA-Z0-9][a-zA-Z0-9._%+-]*@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $str) && strlen($str) <= $max);
    }

    private function validarNombreApellido($str, $max) {
        /* Verifica si la cadena $str cumple con ciertos criterios de caracteres (contiene letras, espacios, tildes, apostrofes o guiones)
        y si la extension de la cadena es menor o igual al maximo especificado por la variable $max. */
        return (preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ '-]+$/", $str) && strlen($str) <= $max);
    }

}