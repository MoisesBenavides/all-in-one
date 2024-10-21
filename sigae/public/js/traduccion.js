// Definir la variable global para almacenar las traducciones
let traducciones;

// Función para cargar las traducciones
function cargarTraducciones(archivo) {
    return fetch(archivo)
        .then(response => response.json())
        .then(data => {
            traducciones = data;
        })
        .catch(error => {
            console.error('Error al cargar el archivo de traducciones:', error);
        });
}

// Función para cambiar el idioma
function cambiarIdioma(idioma) {
    if (!traducciones) {
        console.error('Las traducciones no han sido cargadas');
        return;
    }

    const elementos = document.querySelectorAll('[traducir]');
    elementos.forEach(elemento => {
        const clave = elemento.getAttribute('traducir');
        const traduccion = traducciones[idioma][clave];
        if (traduccion) {
            elemento.textContent = traduccion;
        } else {
            console.warn(`No se encontró traducción para la clave "${clave}" en el idioma "${idioma}"`);
        }
    });
}

// Función para inicializar la traducción
function inicializarTraduccion(archivoTraduccion, idiomaInicial = 'es') {
    cargarTraducciones(archivoTraduccion)
        .then(() => {
            cambiarIdioma(idiomaInicial);
            
            // Agregar event listeners a los botones de idioma si existen
            const botonesIdioma = document.querySelectorAll('[data-idioma]');
            botonesIdioma.forEach(boton => {
                boton.addEventListener('click', (e) => {
                    const idioma = e.target.getAttribute('data-idioma');
                    cambiarIdioma(idioma);
                });
            });
        });
}