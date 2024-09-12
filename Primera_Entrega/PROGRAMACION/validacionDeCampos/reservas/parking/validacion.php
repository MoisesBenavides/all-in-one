<?php
if (isset($_POST["nroPlaza"]) && !empty($_POST["nroPlaza"]) && strlen($_POST["nroPlaza"]) <= 3){
    if (isset($_POST["fechaInicio"], $_POST["fechaFin"]) && !empty($_POST["fechaInicio"]) && !empty($_POST["fechaFin"])) {

        if (isset($_POST["tipoPlaza"])){
            $tipoPlaza="M";
        } else
            $tipoPlaza="A";

        if (isset($_POST["lrgPlazo"])){
            $lrgPlazo=0;
        } else
            $lrgPlazo=1;

    $nroPlaza = $_POST["nroPlaza"];
    $fechaInicio = $_POST["fechaInicio"];
    $fechaFin = $_POST["fechaFin"];

    echo "¡Registro exitoso!";
    echo "<br>";
    echo "Datos de reserva: ";
    echo "<br>";
    echo "Fecha de inicio: $fechaInicio";
    echo "<br>";
    echo "Fecha de fin: $fechaFin";
    echo "<br>";
    echo "Plaza reservada: $nroPlaza";
    echo "<br>";
    echo "Tipo de plaza (Auto o Moto): $tipoPlaza";
    echo "<br>";
    echo "Reserva de largo plazo: $lrgPlazo";

    } else
        echo "Debe elegir una fecha de inicio y fin.";
} else
    echo "Debe ingresar un número de plaza.";
