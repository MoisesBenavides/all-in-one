// Variables globales para almacenar las traducciones
let traduccionesHeader = {};
let traduccionesVista = {};

// Función para cargar las traducciones
function cargarTraducciones(archivo, tipo) {
    return fetch(archivo)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (tipo === 'header') {
                traduccionesHeader = data;
            } else if (tipo === 'vista') {
                traduccionesVista = data;
            }
            console.log(`Traducciones de ${tipo} cargadas correctamente:`, data);
        })
        .catch(error => {
            console.error(`Error al cargar el archivo de traducciones de ${tipo}:`, error);
        });
}

// Función para cambiar el idioma
function cambiarIdioma(idioma) {
    aplicarTraducciones(traduccionesHeader[idioma], 'header');
    aplicarTraducciones(traduccionesVista[idioma], 'vista');
}

// Función para aplicar las traducciones
function aplicarTraducciones(traducciones, tipo) {
    if (!traducciones) {
        console.error(`Traducciones no disponibles para ${tipo}`);
        return;
    }

    const elementos = document.querySelectorAll(`[traducir]`);
    elementos.forEach(elemento => {
        const clave = elemento.getAttribute('traducir');
        if (traducciones[clave]) {
            elemento.textContent = traducciones[clave];
        }
    });
}

// Función para inicializar la traducción
function inicializarTraduccion(archivoTraduccionHeader, archivoTraduccionVista, idiomaInicial = 'es') {
    Promise.all([ //hace que se carguen las dos traducciones al mismo tiempo y solo si las dos funcionann
        cargarTraducciones(archivoTraduccionHeader, 'header'),
        cargarTraducciones(archivoTraduccionVista, 'vista')
    ]).then(() => {
        cambiarIdioma(idiomaInicial);

        // Configurar los botones de idioma
        document.querySelectorAll('.language-button').forEach(button => {
            button.addEventListener('click', (e) => {
                const idioma = e.currentTarget.getAttribute('data-idioma');
                cambiarIdioma(idioma);
            });
        });
    });
}

// Exponer las funciones necesarias globalmente
window.inicializarTraduccion = inicializarTraduccion;
window.cambiarIdioma = cambiarIdioma;