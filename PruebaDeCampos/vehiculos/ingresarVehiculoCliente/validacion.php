<?php

if (isset($_POST["matric"], $_POST["marca"], $_POST["mod"], $_POST["email"]) 
    && !empty($_POST["matric"]) && !empty($_POST["marca"]) 
    && !empty($_POST["mod"]) && !empty($_POST["email"])){

    $email = $_POST["email"];
    $matric = $_POST["matric"];
    $marca = $_POST["marca"];
    $mod = $_POST["mod"];
    
    if (preg_match("/^[a-zA-Z]+/", $marca)){
        if (preg_match("/^[a-zA-Z0-9].*@.+/", $email)) {
            if (preg_match("/^[a-zA-Z]+[0-9]+/", $matric) && strlen($matric) <= 8){
                echo "¡Registro exitoso!";
                echo "<br>";
                echo "Datos de registro: ";
                echo "<br>";
                echo "Marca: $marca";
                echo "<br>";
                echo "Modelo: $mod";
                echo "<br>";
                echo "Matrícula: $matric";
                echo "<br>";
                echo "Mail: $email";
            } else 
                echo "La matrícula ingresada es inválida.";
        } else
            echo "El correo ingresado es inválido";
    } else
        echo "La marca ingresada es inválida";
} else
    echo "Debe llenar todos los campos.";