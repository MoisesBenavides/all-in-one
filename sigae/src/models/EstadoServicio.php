<?php

namespace Sigae\Models;

enum EstadoServicio: string {
    case Pendiente = 'pendiente';
    case Realizado = 'realizado';
    case Cancelado = 'cancelado';
}