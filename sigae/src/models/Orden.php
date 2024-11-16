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
    private EstadoPagoOrden $estado_pago;

    public function __construct($id, $total, $fecha_orden, EstadoPagoOrden $estado_pago){
        $this->id = $id;
        $this->total = $total;
        $this->fecha_orden = $fecha_orden;
        $this->estado_pago = $estado_pago;
    }

    public function getId(){
        return $this->id;
    }

    public function setId($id){
        $this->id = $id;

        return $this;
    }
 
    public function getTotal(){
        return $this->total;
    }
 
    public function setTotal($total){
        $this->total = $total;

        return $this;
    }

    public function getFecha_orden(){
        return $this->fecha_orden;
    }

    public function setFecha_orden($fecha_orden){
        $this->fecha_orden = $fecha_orden;

        return $this;
    }

    public function getEstadoPago(): string{
        return $this->estado_pago->value;
    }

    public function setEstadoPago(EstadoPagoOrden $estado){
        $this->estado_pago = $estado;
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

    public function comenzarTransaccion() {
        if ($this->conn) {
            $this->conn->beginTransaction();
        }
    }

    public function confirmarTransaccion() {
        if ($this->conn) {
            $this->conn->commit();
        }
    }

    public function deshacerTransaccion() {
        if ($this->conn) {
            $this->conn->rollback();
        }
    }

    public function cerrarDBConnection(){
        $this->conn = null;
    }

    public function preparar($id_cliente){
        $total=$this->getTotal();
        $fecha_orden = $this->getFecha_orden();
        $estado_pago=$this->getEstadoPago();

        try {
            $stmt = $this->conn->prepare('INSERT INTO orden (id_cliente, total, fecha_orden, estado_pago) 
                                    VALUES (:id_cl, :tot, :fecha, :est)');

            $stmt->bindParam(':id_cl', $id_cliente);
            $stmt->bindParam(':tot', $total);
            $stmt->bindParam(':fecha', $fecha_orden);
            $stmt->bindParam(':est', $estado_pago);
            
            $stmt->execute();
            $this->setId($this->conn->lastInsertId());

        } catch (Exception $e) {
            throw new Exception("Error procesando la orden: " . $e->getMessage());
        }

    }

    public function agregarDetalleProducto($id_producto, $cantidad){
        $id_orden=$this->getId();
        try {
            $stmt = $this->conn->prepare('INSERT INTO detalle_orden_producto (id_producto, id_orden, cantidad) 
                                    VALUES (:id_prod, :id_ord, :cant)');

            $stmt->bindParam(':id_prod', $id_producto);
            $stmt->bindParam(':id_ord', $id_orden);
            $stmt->bindParam(':cant', $cantidad);
            
            $stmt->execute();

        } catch (Exception $e) {
            throw new Exception("Error incluyendo el producto: ID ".$id_producto." :". $e->getMessage());
        }
    }

    public function agregarDetalleServicio($id_servicio){
        $id_orden = $this->getId();
        try {
            $stmt = $this->conn->prepare('INSERT INTO detalle_orden_servicio (id_servicio, id_orden) 
                                        VALUES (:id_serv, :id_ord)');

            $stmt->bindParam(':id_serv', $id_servicio);
            $stmt->bindParam(':id_ord', $id_orden);

            $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Error incluyendo el servicio: ID " . $id_servicio . " :" . $e->getMessage());
        }
    }
        
    public static function obtenerIngresosBrutosProd($rol, $fechaIni, $fechaFin){
        try{
            $conn = conectarDB($rol);
            if($conn === false){
                throw new Exception("No se puede conectar con la base de datos.");
            }

            $stmt = $conn->prepare('SELECT  p.id, p.marca, p.precio, p.archivado, 
                                            n.modelo, n.tamano, n.tipo, op.nombre, 
                                            SUM(dp.cantidad) AS cant_vendidos, SUM(p.precio * dp.cantidad) AS ingreso_bruto 
                                    FROM detalle_orden_producto dp 
                                    JOIN producto p ON dp.id_producto = p.id 
                                    LEFT JOIN neumatico n ON p.id = n.id_producto 
                                    LEFT JOIN otro_producto op ON p.id = op.id_producto 
                                    WHERE dp.id_orden IN (
                                        SELECT o.id 
                                        FROM orden o 
                                        WHERE o.estado_pago = "pago" 
                                        AND o.fecha_orden <= :fecha_fin 
                                        AND o.fecha_orden >= :fecha_ini
                                    ) 
                                    GROUP BY p.id 
                                    ORDER BY ingreso_bruto DESC');

            $stmt->bindParam(':fecha_fin', $fechaFin);
            $stmt->bindParam(':fecha_ini', $fechaIni);

            $stmt->execute();

            $ingresosBrutos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $ingresosBrutos;

        } catch(Exception $e){
            throw new Exception("Error al obtener ingresos de productos: ".$e->getMessage());
            return;
        } finally {
            $conn = null;
        }



    }

    public function getOrdenes(){
    }
    public function getProductosIncluidos(){
    }
    public function getServiciosIncluidos(){
    }
}
