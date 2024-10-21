// Definir la variable global para almacenar las traducciones
let traducciones;

// Función para cargar las traducciones
function cargarTraducciones(archivo) {
    return fetch(archivo)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            traducciones = data;
            console.log('Traducciones cargadas correctamente:', traducciones);
        })
        .catch(error => {
            console.error('Error al cargar el archivo de traducciones:', error);
            // Aquí podrías establecer un conjunto de traducciones por defecto o mostrar un mensaje al usuario
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
        if (traducciones[idioma] && traducciones[idioma][clave]) {
            elemento.textContent = traducciones[idioma][clave];
        } else {
            console.warn(`No se encontró traducción para la clave "${clave}" en el idioma "${idioma}"`);
        }
    });
}

// Función para inicializar la traducción
function inicializarTraduccion(archivoTraduccion, idiomaInicial = 'es') {
    cargarTraducciones(archivoTraduccion)
        .then(() => {
            if (traducciones) {
                cambiarIdioma(idiomaInicial);
                
                // Agregar event listeners a los botones de idioma si existen
                const botonesIdioma = document.querySelectorAll('[data-idioma]');
                botonesIdioma.forEach(boton => {
                    boton.addEventListener('click', (e) => {
                        const idioma = e.target.getAttribute('data-idioma');
                        cambiarIdioma(idioma);
                    });
                });
            }
        });
}

// Exponer la función inicializarTraduccion globalmente
window.inicializarTraduccion = inicializarTraduccion;