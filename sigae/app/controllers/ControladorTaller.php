<?php

class ControladorTaller{
    private $taller;

    public function __construct(){
        $this->taller = new Taller();
    }

    function doBookService(){
        session_start();
        error_log(print_r($_SESSION, true));
    }
}