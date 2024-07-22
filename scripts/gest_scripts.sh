#!/bin/bash

inicio_sesion(){
clear
echo "Inicio de sesion"
echo -n "Usuario: "
read usuario
echo -n "Contrasena: "
read -s contrasena
echo 

temp_file=$(mktemp)

echo "$contrasena" | su -c "exit" "$usuario" &>/dev/null
if [ $? -eq 0 ]; then
echo "Inicio de sesion exitoso"
rm -f "$temp_file"
menu_principal
else
echo "Usuario o contrasena incorrectos"
rm -f "$temp_file"
exit 1
fi
}

menu_principal(){
clear
echo "Menu Principal"
echo "1. Operaciones con Usuarios"
echo "2. Operaciones con Grupos"
echo "3. Salir"
echo -n "Seleccione una opcion: "
read opcion
case $opcion in
1) menu_usuarios ;;
2) menu_grupos ;;
3) exit 0 ;;
*) echo "Opcion no Valida"; menu_principal ;;
esac
}

menu_usuarios(){
clear
echo "Operaciones con Usuarios"
echo "1. Crear Usuario"
echo "2. Eliminar Usuario"
echo "3. Modificar Usuario"
echo "4. Consultar Usuario"
echo "5. Volver al Menu Principal"
echo -n "Seleccione una Opcion: "
read opcion
case $opcion in
1) crear_usuario ;;
2) eliminar_usuario ;;
3) modificar_usuario ;;
4) consultar_usuario ;;
5) menu_principal ;;
*) echo "Opcion no Valida"; menu_usuarios ;;
esac
}

menu_grupos(){
clear
echo "Operaciones con grupos"
echo "1. Crear Grupo"
echo "2. Eliminar Grupo"
echo "3. Modificar Grupo"
echo "4. Consultar Grupo"
echo "5. Volver al Menu Principal"
echo -n "Seleccione una Opcion: "
read opcion
case $opcion in
1) crear_grupo ;;
2) eliminar_grupo ;;
3) modificar_grupo ;;
4) consultar_grupo ;;
*) echo "Opcion no Valida"; menu_grupos ;;
esac
}

crear_usuario(){
clear
echo -n "Nombre del usuario a crear: "
read nombre_usuario
sudo adduser $nombre_usuario
echo "Ususario agregado con exito"
menu_principal
}

eliminar_usuario(){
clear
echo -n "Nombre del usuario a eliminar: "
read nombre_usuario
sudo deluser $nombre_usuario
echo "Usuario eliminado con exito"
menu_principal
}

modificar_usuario(){
clear
echo -n "Nombre del usuario a modificar: "
read nombre_usuario
sudo passwd $nombre_usuario
menu_usuarios
}

consultar_usuario(){
clear
echo -n "Nombre del usuario a consultar: "
read nombre_usuario
id $nombre_usuario
menu_usuarios
}

crear_grupo(){
clear
echo -n "Nombre del grupo a crear: "
read nombre_grupo
sudo addgroup $nombre_grupo
echo "Grupo agregado con exito"
menu_grupos
}

eliminar_grupo(){
clear
echo -n "Nombre del grupo a eliminar: "
read nombre_grupo
sudo delgroup $nombre_grupo
echo "Grupo eliminado con exito"
menu_grupos
}

modificar_grupo(){
clear
echo -n "Nombre del grupo a modificar: "
read nombre_grupo
echo -n "Nombre del usuario a agregar al grupo: "
read nombre_usuario
sudo adduser $nombre_usuario $nombre_grupo
menu_grupos
}

consultar_grupo(){
clear
echo -n "Nombre del grupo a consultar: "
read nombre_grupo
getnet group $nombre_grupo
menu_grupos
}

inicio_sesion

