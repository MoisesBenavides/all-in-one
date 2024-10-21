// Variables globales para almacenar las traducciones
let traducciones = {};

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
            Object.assign(traducciones, data);
            console.log('Traducciones cargadas correctamente:', traducciones);
        })
        .catch(error => {
            console.error('Error al cargar el archivo de traducciones:', error);
        });
}

// Función para cambiar el idioma
function cambiarIdioma(idioma) {
    if (!traducciones || !traducciones[idioma]) {
        console.error(`Traducciones no disponibles para el idioma "${idioma}"`);
        return;
    }

    const elementos = document.querySelectorAll('[traducir]');
    elementos.forEach(elemento => {
        const clave = elemento.getAttribute('traducir');
        if (traducciones[idioma] && traducciones[idioma][clave]) {
            elemento.textContent = traducciones[idioma][clave];
        }
    });
}

// Función para inicializar la traducción
function inicializarTraduccion(archivoTraduccion, idiomaInicial = 'es') {
    cargarTraducciones(archivoTraduccion)
        .then(() => {
            cambiarIdioma(idiomaInicial);
        });
}

// Exponer las funciones necesarias globalmente
window.inicializarTraduccion = inicializarTraduccion;
window.cambiarIdioma = cambiarIdioma;