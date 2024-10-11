<?php

namespace Sigae\models;

enum EstadoServicio: string {
    case Pendiente = 'pendiente';
    case Realizado = 'realizado';
    case Cancelado = 'cancelado';
}