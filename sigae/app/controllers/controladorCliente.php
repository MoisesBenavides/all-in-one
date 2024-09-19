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
        //implementar validacion
        //enviar formulario a modelo Cliente
        //redireccionar a home
        include '../app/views/client/homeCliente.html';
    }
    function signup(){
        include '../app/views/account/signup.html';
    }
    function doSignup(){
        // Validacion de campos vacios
        if (isset($_POST["email"], $_POST["nombre"], $_POST["apellido"], $_POST["contrasena"], $_POST["repContrasena"]) && !empty($_POST["email"]) 
        && !empty($_POST["nombre"]) && !empty($_POST["apellido"]) && !empty($_POST["contrasena"]) && !empty($_POST["repContrasena"])){
            
            $email = $_POST["email"];
            $nombre = $_POST["nombre"];
            $apellido = $_POST["apellido"];
            $contrasena = $_POST["contrasena"];
            $repContrasena = $_POST["repContrasena"];
            
            if (validarEmail($email, 63)) {
                if (validarNombreApellido($nombre, 23) && validarNombreApellido($apellido, 23)) {
                    // validar contraseña
                    if($contrasena==$repContrasena){
                        $this->cliente->agregarCliente($email, $contrasena, $nombre, $apellido);
                    } else {
                        echo "Las contraseñas no coinciden.";
                    }
                } else {
                    echo "Por favor, ingrese un nombre o apellido válido.";
                }
            } else {
                echo "Por favor, ingrese un correo electrónico válido.";
            }
            
        } else{
            echo "Debe llenar todos los campos.";
        }

        //enviar formulario a modelo Cliente
        //redireccionar a home
        include '../app/views/client/homeCliente.html';
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

    private function validarNombreApellido($str, $ext){
        $esValido;

        /* Verifica si la cadena $str cumple con ciertos criterios de caracteres (contiene letras, espacios, tildes, apostrofes o guiones)
        y si la extension de la cadena es menor o igual al maximo especificado por la variable $ext. */
        if (preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ '-]+$/", $str) && strlen($str) <= $ext){
            $esValido = true;
        } else {
            $esValido = false;
        }
        return $esValido;
    }

    private function validarEmail($str, $ext){
        $esValido;

        /* Verifica si la cadena $str cumple con ciertos criterios de caracteres y contiene un dominio de correo valido
        y si la extension de la cadena es menor o igual al maximo especificado por la variable $ext. */ 
        if (preg_match("/^[a-zA-Z0-9][a-zA-Z0-9._%+-]*@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $str) && strlen($str) <= $ext){
            $esValido = true;
        } else {
            $esValido = false;
        }
        return $esValido;
    }
}