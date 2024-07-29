<?php

if (isset($_POST["matric"], $_POST["email"]) 
    && !empty($_POST["matric"]) && !empty($_POST["email"])){

    $email = $_POST["email"];
    $matric = $_POST["matric"];
    
    if (preg_match("/^[a-zA-Z0-9].*@.+/", $email)) {
            if (preg_match("/^[a-zA-Z]+[0-9]+/", $matric) && strlen($matric) <= 8){
                echo "¡Registro exitoso!";
                echo "<br>";
                echo "Datos de registro: ";
                echo "<br>";
                echo "Matrícula: $matric";
                echo "<br>";
                echo "Mail: $email";
            } else 
                echo "La matrícula ingresada es inválida.";
    } else
        echo "El correo ingresado es inválido";
} else
    echo "Debe llenar todos los campos.";