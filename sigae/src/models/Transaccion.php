<?php

namespace Sigae\Models;
use function Sigae\Config\conectarDB;
use PDO;
use Exception;

class Transaccion{
    private ?PDO $conn =null;
    private TipoTransaccion $tipo;
    private $cantidad;

    public function __construct(TipoTransaccion $tipo, $cantidad){
        $this->tipo = $tipo;
        $this->cantidad = $cantidad;
    }

    public function setDBConnection($rol){
        $this->conn = conectarDB($rol);
        if($this->conn === false){
            throw new Exception("No se puede conectar con la base de datos.");
        }
        return $this;
    }

    public function getDBConnection(){
        return $this->conn;
    }

    public function getTipo(){
        return $this->tipo;
    }

    public function setTipo($tipo){
        $this->tipo = $tipo;

        return $this;
    }

    public function getCantidad(){
        return $this->cantidad;
    }

    public function setCantidad($cantidad){
        $this->cantidad = $cantidad;

        return $this;
    }

    public function comenzarTransaccion() {
        if ($this->conn) {
            $this->conn->beginTransaction();
        }
    }

    // Método para confirmar una transacción
    public function confirmarTransaccion() {
        if ($this->conn) {
            $this->conn->commit();
        }
    }

    // Método para revertir una transacción
    public function deshacerTransaccion() {
        if ($this->conn) {
            $this->conn->rollback();
        }
    }

    public function cerrarDBConnection(){
        $this->conn = null;
    }

    public function realizarTransaccion(){
    }
    
}