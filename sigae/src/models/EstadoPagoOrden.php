<?php

namespace Sigae\Models;

enum EstadoPagoOrden: string {
    case No_pago = 'no pago';
    case Pago = 'pago';
    case Cancelado = 'cancelado';
}