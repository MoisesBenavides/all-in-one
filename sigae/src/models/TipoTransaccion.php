<?php

namespace Sigae\Models;

enum TipoTransaccion: String{
    case Ingreso = 'ingreso';
    case Egreso = 'egreso';
}