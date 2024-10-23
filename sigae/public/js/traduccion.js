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
    if (!archivo) {
        console.error(`Ruta de archivo no válida para ${tipo}`);
        return Promise.reject('Ruta de archivo no válida');
    }

    return fetch(archivo)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP ${response.status} al cargar ${tipo}`);
            }
            return response.json();
        })
        .then(data => {
            console.log(`Traducciones cargadas para ${tipo}:`, data);
            if (tipo === 'header') {
                traduccionesHeader = data;
            } else if (tipo === 'vista') {
                traduccionesVista = data;
            }
            return data;
        })
        .catch(error => {
            console.error(`Error al cargar traducciones de ${tipo}:`, error);
            return {};
        });
}

// Función para cambiar el idioma
function cambiarIdioma(idioma) {
    console.log('Cambiando idioma a:', idioma);
    
    if (traduccionesHeader[idioma]) {
        aplicarTraducciones(traduccionesHeader[idioma], 'header');
    } else {
        console.warn('No se encontraron traducciones del header para el idioma:', idioma);
    }
    
    if (traduccionesVista[idioma]) {
        aplicarTraducciones(traduccionesVista[idioma], 'vista');
    } else {
        console.warn('No se encontraron traducciones de la vista para el idioma:', idioma);
    }
    
    setCookie('selectedLanguage', idioma, 30);
}

// Función para aplicar las traducciones
function aplicarTraducciones(traducciones, tipo) {
    if (!traducciones) {
        console.warn(`No hay traducciones disponibles para ${tipo}`);
        return;
    }
    document.querySelectorAll('[traducir]').forEach(elemento => {
        const clave = elemento.getAttribute('traducir');
        if (traducciones[clave]) {
            if (elemento.tagName === 'INPUT') {
                elemento.placeholder = traducciones[clave];
            } else {
                elemento.textContent = traducciones[clave];
            }
        } else {
            console.warn(`No se encontró traducción para la clave "${clave}" en ${tipo}`);
        }
    });
}


// Función para inicializar la traducción
function inicializarTraduccion(archivoTraduccionHeader, archivoTraduccionVista, idiomaInicial = 'es') {
    console.log('Cargando traducciones con archivos:', {
        header: archivoTraduccionHeader,
        vista: archivoTraduccionVista
    });

    // Primero cargamos las traducciones
    Promise.all([
        cargarTraducciones(archivoTraduccionHeader, 'header'),
        cargarTraducciones(archivoTraduccionVista, 'vista')
    ]).then(() => {
        // Configurar los botones de idioma
        document.querySelectorAll('[data-idioma]').forEach(button => {
            button.addEventListener('click', (e) => {
                const idioma = e.target.closest('[data-idioma]').getAttribute('data-idioma');
                cambiarIdioma(idioma);
            });
        });

        // Obtener el idioma guardado o usar el inicial
        const idiomaGuardado = getCookie('selectedLanguage');
        const idiomaAUsar = idiomaGuardado || idiomaInicial;
        
        // Aplicar el idioma inicial
        cambiarIdioma(idiomaAUsar);
    }).catch(error => {
        console.error("Error al inicializar las traducciones:", error);
    });
}

// Exponer las funciones necesarias globalmente
window.inicializarTraduccion = inicializarTraduccion;
window.cambiarIdioma = cambiarIdioma;