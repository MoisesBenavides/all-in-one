<?php

if (isset($_POST["email"], $_POST["nom"], $_POST["ape"], $_POST["tel"], $_POST["pswd"], $_POST["pswdConf"]) 
    && !empty($_POST["email"]) && !empty($_POST["nom"]) && !empty($_POST["ape"]) 
    && !empty($_POST["tel"]) && !empty($_POST["pswd"]) && !empty($_POST["pswdConf"])) {

    $email = $_POST["email"];
    $nom = $_POST["nom"];
    $ape = $_POST["ape"];
    $tel = $_POST["tel"];
    $pswd = $_POST["pswd"];
    $pswdConf = $_POST["pswdConf"];
    
    if (preg_match("/^[0-9]{8}/", $tel)){
        if ($pswd === $pswdConf) {
            if (preg_match("/^[a-zA-Z0-9].*@.+/", $email)) {
                echo "¡Registro exitoso!";
                echo "<br>";
                echo "Datos de registro: ";
                echo "<br>";
                echo "Nombre: $nom";
                echo "<br>";
                echo "Apellido: $ape";
                echo "<br>";
                echo "Mail: $email";
                echo "<br>";
                echo "Teléfono: (+598) $tel";
                echo "<br>";
                echo "(Contraseña): $pswd";
            } else {
                echo "El correo ingresado es inválido";
            }
        } else {
            echo "Las contraseñas ingresadas no coinciden.";
        }
    } else
        echo "Teléfono inválido.";
} else
    echo "Debe llenar todos los campos.";
