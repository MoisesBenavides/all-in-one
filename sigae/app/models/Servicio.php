<?php

require_once 'EstadoServicio.php';

abstract class Servicio{
    protected $id;
    protected $precio;
    protected $fecha_inicio;
    protected $fecha_final;
    protected EstadoServicio $estado;

    public function __construct($id, $precio, $fecha_inicio, $fecha_final){
        $this->precio = $precio;
        $this->fecha_inicio = $fecha_inicio;
        $this->fecha_final = $fecha_final;
        $this->estado = EstadoServicio::Pendiente;
    }

    public function getId(){
        return $this->id;
    }

    public function setId($id){
        $this->id = $id;

        return $this;
    }

    public function getPrecio(){
        return $this->precio;
    }

    public function setPrecio($precio){
        $this->precio = $precio;

        return $this;
    }

    public function getFecha_inicio(){
        return $this->fecha_inicio;
    }

    public function setFecha_inicio($fecha_inicio){
        $this->fecha_inicio = $fecha_inicio;

        return $this;
    }

    public function getFecha_final(){
        return $this->fecha_final;
    }

    public function setFecha_final($fecha_final){
        $this->fecha_final = $fecha_final;

        return $this;
    }

    public function getEstado(): string{
        return $this->estado->value;
    }

    public function setEstado(EstadoServicio $estado){
        $this->estado = $estado;
    }

    abstract public function reservarServicio($matricula);

    abstract public function cambiarServicio();

    abstract public function cancelarServicio();

    abstract public function getServicios();
}