<?php

namespace Sigae\Models;

abstract class Producto{
    protected $id;
    protected $upc;
    protected $precio;
    protected $marca;
    protected $fecha_creacion;
    protected $stock;
    
    public function getId(){
        return $this->id;
    }

    public function setId($id){
        $this->id = $id;
    }

    public function getUpc(){
        return $this->upc;
    }

    public function setUpc($upc){
        $this->upc = $upc;
    }

    public function getPrecio(){
        return $this->precio;
    }

    public function setPrecio($precio){
        $this->precio = $precio;
    }

    public function getMarca(){
        return $this->marca;
    }

    public function setMarca($marca){
        $this->marca = $marca;
    }

    public function getFechaCreacion(){
        return $this->fecha_creacion;
    }

    public function setFechaCreacion($fecha_creacion){
        $this->fecha_creacion = $fecha_creacion;
    }

    public function getStock(){
        return $this->stock;
    }

    public function setStock($stock){
        $this->stock = $stock;
    }

    public function getProductos(){
    }
    abstract public function agregarStock();
    abstract public function restarStock(); 

}