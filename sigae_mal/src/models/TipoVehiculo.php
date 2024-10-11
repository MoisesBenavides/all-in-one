<?php

namespace Sigae\models;

enum TipoVehiculo: String{
    case Moto = 'moto';
    case Auto = 'auto';
    case Camioneta = 'camioneta';
    case Camion = 'camion';
    case Utilitario = 'utilitario';
}