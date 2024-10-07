<?php

function ciValida($ci){
    $digitosDeCedula = str_split($ci);  // Convierte la cédula en un array de caracteres

    $numerosParaMultiplicar = array(2, 9, 8, 7, 6, 3, 4);

    $acum = 0;
    for ($i = 0; $i < count($digitosDeCedula) - 1; $i++) {
        $acum += $digitosDeCedula[$i] * $numerosParaMultiplicar[$i];
    }
    $aux = $acum % 10;
    $verif = 10 - $aux;

    return $verif == $digitosDeCedula[count($digitosDeCedula) - 1];
}