// Funciones para manejar cookies
function setCookie(name, value, days) {
    let expires = "";
    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + value + expires + "; path=/";
}

function getCookie(name) {
    const nameEQ = name + "=";
    const ca = document.cookie.split(';');
    for(let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

// Variables globales para almacenar las traducciones
let traduccionesHeader = {};
let traduccionesVista = {};

// Función para cargar las traducciones
function cargarTraducciones(archivo, tipo) {
    return fetch(archivo)
        .then(response => {
            if (!response.ok) {
                console.error(`Error al cargar traducciones (${tipo}):`, response.status);
                return {};
            }
            return response.json();
        })
        .then(data => {
            if (tipo === 'header') {
                traduccionesHeader = data;
            } else if (tipo === 'vista') {
                traduccionesVista = data;
            }
            return data;
        })
        .catch(error => {
            console.error(`Error al procesar traducciones (${tipo}):`, error);
            return {};
        });
}

// Función para cambiar el idioma
function cambiarIdioma(idioma) {
    aplicarTraducciones(traduccionesHeader[idioma], 'header');
    aplicarTraducciones(traduccionesVista[idioma], 'vista');
    // Guardar el idioma seleccionado en una cookie que dura 30 días
    setCookie('selectedLanguage', idioma, 30);
}

// Función para aplicar las traducciones
function aplicarTraducciones(traducciones, tipo) {
    if (!traducciones || Object.keys(traducciones).length === 0) {
        console.warn(`Traducciones no disponibles para ${tipo}`);
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
    // Verificar si existe una cookie con un idioma guardado
    const idiomaGuardado = getCookie('selectedLanguage');
    const idiomaAUsar = idiomaGuardado || idiomaInicial;

    Promise.all([
        cargarTraducciones(archivoTraduccionHeader, 'header'),
        cargarTraducciones(archivoTraduccionVista, 'vista')
    ]).then(() => {
        cambiarIdioma(idiomaAUsar);

        // Usar data-idioma en lugar de class
        document.querySelectorAll('[data-idioma]').forEach(button => {
            button.addEventListener('click', () => {
                const idioma = button.getAttribute('data-idioma');
                cambiarIdioma(idioma);
            });
        });
    }).catch(error => {
        console.error("Error al inicializar las traducciones:", error);
    });
}
// Exponer las funciones necesarias globalmente
window.inicializarTraduccion = inicializarTraduccion;
window.cambiarIdioma = cambiarIdioma;