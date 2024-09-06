<?php
function verificar($ci) {
    $digitosDeCedula = str_split($ci);      //hace un array de caracteres con los digitos de la cedula
    $numerosParaMultiplicar = array(2, 9, 8, 7, 6, 3, 4);

    $acum = 0;
    for ($i = 0; $i < count($digitosDeCedula) - 1; $i++) {
        $acum += $digitosDeCedula[$i] * $numerosParaMultiplicar[$i];
    }
    $aux = $acum % 10;
    $verif = 10 - $aux;

    if ($verif == $digitosDeCedula[count($digitosDeCedula) - 1]) {
        return true;
    } else {
        return false;
    }
}
?>