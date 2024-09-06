<?php
include 'verificarCI.php';

if (isset($_POST["ci"], $_POST["email"], $_POST["nom"], $_POST["ape"], $_POST["tel"], $_POST["pswd"], 
    $_POST["pswdConf"], $_POST["calle"], $_POST["nroPuerta"], $_POST["mutu"])

    && !empty($_POST["ci"]) && !empty($_POST["email"]) && !empty($_POST["nom"]) && !empty($_POST["ape"]) 
    && !empty($_POST["mutu"]) && !empty($_POST["calle"]) && !empty($_POST["nroPuerta"]) 
    && !empty($_POST["tel"]) && !empty($_POST["pswd"]) && !empty($_POST["pswdConf"])) {

    $ci = $_POST["ci"];
    $email = $_POST["email"];
    $nom = $_POST["nom"];
    $ape = $_POST["ape"];
    $tel = $_POST["tel"];
    $pswd = $_POST["pswd"];
    $pswdConf = $_POST["pswdConf"];
    $calle = $_POST["calle"];
    $nroPuerta = $_POST["nroPuerta"];
    $mutu = $_POST["mutu"];
    
    if (preg_match("/^[0-9]{8}/", $tel)){
        if ($pswd === $pswdConf) {
            if (preg_match("/^[a-zA-Z0-9].*@.+/", $email)) {
                if (verificar($ci)){
                    echo "¡Registro exitoso!";
                    echo "<br>";
                    echo "Datos de registro: ";
                    echo "<br>";
                    echo "Nombre: $nom";
                    echo "<br>";
                    echo "Apellido: $ape";
                    echo "<br>";
                    echo "CI: $ci";
                    echo "<br>";
                    echo "Mail: $email";
                    echo "<br>";
                    echo "Teléfono: (+598) $tel";
                    echo "<br>";
                    echo "Dirección: $calle, $nroPuerta";
                    echo "<br>";
                    echo "Mutualista: $mutu";
                    echo "<br>";
                    echo "(Contraseña): $pswd";
                } else 
                    echo "La cédula de identidad ingresada es inválida.";
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
