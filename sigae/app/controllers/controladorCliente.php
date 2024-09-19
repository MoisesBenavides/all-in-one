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
        //implementar validacion
        //enviar formulario a modelo Cliente
        //redireccionar a home
        include '../app/views/client/homeCliente.html';
    }
    function forgotPassword(){
        //implementar validacion
        //enviar formulario a modelo Cliente
        //redireccionar a login
        include '../app/views/client/login.html';
    }
    function home(){
        include '../app/views/client/homeCliente.html';
    }
}