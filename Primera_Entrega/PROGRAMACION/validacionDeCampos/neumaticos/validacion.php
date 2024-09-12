<?php
if (isset($_POST["idNeumatico"], $_POST["upc"], $_POST["marca"], 
    $_POST["mod"], $_POST["tmano"], $_POST["tipo"], $_POST["precio"]) 

    && !empty($_POST["idNeumatico"]) && !empty($_POST["upc"]) && !empty($_POST["marca"])
    && !empty($_POST["mod"]) && !empty($_POST["tmano"]) && !empty($_POST["tipo"]) && !empty($_POST["precio"]) ){

        $idNeumatico = (string)$_POST["idNeumatico"];
        $upc = (string)$_POST["upc"];
        $marca = $_POST["marca"];
        $mod = $_POST["mod"];
        $tmano = $_POST["tmano"];
        $tipo = $_POST["tipo"];
        $precio = $_POST["precio"];

    if (preg_match("/^[a-zA-Z]+$/", $marca)){
        if (strlen($idNeumatico) == 12) {
            if (strlen($upc) == 13){
                if ($precio > 0){
                    if (preg_match("/^\d{3}\/\d{2}R\d{2}$/", $tmano) && preg_match("/^[a-zA-Z]{2}$/", $tipo)){
                        echo "¡Registro exitoso!";
                        echo "<br>";
                        echo "Datos de registro: ";
                        echo "<br>";
                        echo "Marca: $marca";
                        echo "<br>";
                        echo "Modelo: $mod";
                        echo "<br>";
                        echo "ID: $idNeumatico";
                        echo "<br>";
                        echo "UPC: $upc";
                        echo "<br>";
                        echo "Tamaño: $tmano";
                        echo "<br>";
                        echo "Tipo: $tipo";
                        echo "<br>";
                        echo "Precio ($ UYU): $precio";
                    } else
                        echo "El tamaño o el tipo es incorrecto.";
                } else
                    echo "El precio es inválido";
            } else 
                echo "El código UPC es inválido.";
        } else
            echo "El ID de neumático es inválido";
    } else
        echo "La marca ingresada es inválida";
} else
    echo "Debe llenar todos los campos.";
?>
