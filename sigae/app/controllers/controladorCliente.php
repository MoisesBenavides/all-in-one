<?php
require_once '../app/models/Cliente.php';

class ControladorCliente{
    private $cliente;
    private $errores = [];

    public function __construct(){
        $this->cliente=new Cliente();
    }
    function login(){
        include '../app/views/account/login.html';
    }
    function doLogin(){
        //implementar validacion
        //enviar formulario a modelo Cliente
        //redireccionar a home
        include '../app/views/client/homeCliente.html';
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

            // validar email
            if ($this->validarEmail($email, 63)) {
                if ($this->validarNombreApellido($nombre, 23) && $this->validarNombreApellido($apellido, 23)) {
                    // validar contraseña
                    if($contrasena==$repContrasena){
                        if($this->cliente->agregarCliente($email, $contrasena, $nombre, $apellido)) {
                            $response['success'] = true;
                            header('Location: index.php?action=home');
                        } else {
                            $response['errors'][] = "Error al agregar cliente.";
                            header('Content-Type: application/json');
                        }
                    } else {
                        $response['errors'][] = "Las contraseñas no coinciden.";
                    }
                } else {
                    $response['errors'][] = "Por favor, ingrese un nombre o apellido válido.";
                }
            } else {
                $response['errors'][] = "Por favor, ingrese un correo electrónico válido.";
            }
        } else{
            $response['errors'][] = "Debe llenar todos los campos.";
            // Debug: Log which fields are missing
            $response['debug']['missing_fields'] = array_diff(
                ['email', 'nombre', 'apellido', 'contrasena', 'repContrasena'],
                array_keys($_POST)
            );
        }
        echo json_encode($response);
        exit;
    }

    function forgotPassword(){
        include '../app/views/account/forgotPassword.html';
    }

    function services(){
        include '../app/views/client/serviciosMecanicos.html';
    }

    function home(){
        include '../app/views/client/homeCliente.html';
    }

    private function validarEmail($str, $ext) {
        /* Verifica si la cadena $str cumple con ciertos criterios de caracteres y contiene un dominio de correo valido
        y si la extension de la cadena es menor o igual al maximo especificado por la variable $ext. */ 
        return (preg_match("/^[a-zA-Z0-9][a-zA-Z0-9._%+-]*@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $str) && strlen($str) <= $ext);
    }

    private function validarNombreApellido($str, $ext) {
        /* Verifica si la cadena $str cumple con ciertos criterios de caracteres (contiene letras, espacios, tildes, apostrofes o guiones)
        y si la extension de la cadena es menor o igual al maximo especificado por la variable $ext. */
        return (preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ '-]+$/", $str) && strlen($str) <= $ext);
    }

}