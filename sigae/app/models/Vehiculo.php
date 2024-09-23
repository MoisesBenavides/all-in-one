<?php

class Vehiculo
{
    private $matricula;
    private $marca;
    private $modelo;
    private $tipo;
    private $color;

    public function __construct($matricula, $marca, $modelo, $tipo, $color)
    {
        $this->matricula = $matricula;
        $this->marca = $marca;
        $this->modelo = $modelo;
        $this->tipo = $tipo;
        $this->color = $color;
    }

    public function getMatricula()
    {
        return $this->matricula;
    }

    public function setMatricula($matricula)
    {
        $this->matricula = $matricula;
    }

    public function getMarca()
    {
        return $this->marca;
    }

    public function setMarca($marca)
    {
        $this->marca = $marca;
    }

    public function getModelo()
    {
        return $this->modelo;
    }

    public function setModelo($modelo)
    {
        $this->modelo = $modelo;
    }

    public function getTipo()
    {
        return $this->tipo;
    }

    public function setTipo($tipo)
    {
        $this->tipo = $tipo;
    }

    public function getColor()
    {
        return $this->color;
    }

    public function setColor($color)
    {
        $this->color = $color;
    }
}