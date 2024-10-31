<?php

namespace Sigae\Models;
use function Sigae\Config\conectarDB;
use PDO;
use Exception;

class Orden{
    private ?PDO $conn =null;
    private $id;
    private $total;
    private $fecha_orden;
    private $estado_pago;

    public function getOrdenes(){
    }
    public function getProductosIncluidos(){
    }
    public function getServiciosIncluidos(){
    }
}
