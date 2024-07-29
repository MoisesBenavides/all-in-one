<?php

/* Cada tipo de servicio tiene un codigo asignado
*/

if (isset($_POST["tipoServicio"]) && !empty($_POST["tipoServicio"]) && strlen($_POST["tipoServicio"]) <= 3){
    if (isset($_POST["fechaInicio"]) && !empty($_POST["fechaInicio"])) {

    $tipoServicio = $_POST["tipoServicio"];
    $fechaInicio = $_POST["fechaInicio"];

    echo "¡Registro exitoso!";
    echo "<br>";
    echo "Datos de reserva: ";
    echo "<br>";
    echo "Servicio agendado: $tipoServicio";
    echo "<br>";
    echo "Fecha de inicio: $fechaInicio";
    echo "<br>";

    } else
        echo "Debe elegir una fecha de inicio.";
} else
    echo "Debe ingresar un tipo de servicio válido.";
