let traduccionesHeader = {};
let traduccionesVista = {};
let rutaArchivoHeader = '';
let rutaArchivoVista = '';

// Función para cargar las traducciones
async function cargarTraducciones(archivo, tipo) {
    if (!archivo) {
        console.error(`Ruta de archivo no válida para ${tipo}`);
        return {};
    }

    try {
        console.log(`Intentando cargar archivo ${tipo}:`, archivo); // debug
        const response = await fetch(archivo);
        if (!response.ok) {
            throw new Error(`Error HTTP ${response.status}`);
        }
        const data = await response.json();
        
        if (tipo === 'header') {
            traduccionesHeader = data;
        } else if (tipo === 'vista' || tipo === 'unico') {
            traduccionesVista = data;
        }
        return data;
    } catch (error) {
        console.error(`Error al cargar traducciones de ${tipo}:`, error);
        return {};
    }
}

// Función para aplicar las traducciones
function aplicarTraducciones(traducciones, tipo) {
    if (!traducciones) return;

    document.querySelectorAll('[traducir]').forEach(elemento => {
        const clave = elemento.getAttribute('traducir');
        if (traducciones[clave]) {
            if (elemento.tagName === 'INPUT') {
                elemento.placeholder = traducciones[clave];
            } else {
                elemento.textContent = traducciones[clave];
            }
        }
    });
}

// Función para cambiar el idioma
async function cambiarIdioma(idioma, skipCookie = false) {
    try {
        // Si hay archivo de header, cargarlo
        if (rutaArchivoHeader) {
            await cargarTraducciones(rutaArchivoHeader, 'header');
        }
        
        // Si hay archivo de vista, cargarlo
        if (rutaArchivoVista) {
            await cargarTraducciones(rutaArchivoVista, 'vista');
        }

        // Aplicar traducciones
        if (traduccionesHeader[idioma]) {
            aplicarTraducciones(traduccionesHeader[idioma], 'header');
        }
        if (traduccionesVista[idioma]) {
            aplicarTraducciones(traduccionesVista[idioma], 'vista');
        }
        
        // Guardar el idioma en cookies solo si no se especifica lo contrario
        if (!skipCookie) {
            setCookie('idioma', idioma, 30);
        }
    } catch (error) {
        console.error('Error al cambiar idioma:', error);
    }
}

// Función para inicializar la traducción
async function inicializarTraduccion(archivoHeader = null, archivoVista = null, idiomaInicial = 'es') {
    try {
        // Guardar las rutas globalmente
        rutaArchivoHeader = archivoHeader;
        rutaArchivoVista = archivoVista;

        // Cargar traducciones iniciales
        const promesas = [];
        if (archivoHeader) {
            promesas.push(cargarTraducciones(archivoHeader, 'header'));
        }
        if (archivoVista) {
            promesas.push(cargarTraducciones(archivoVista, 'vista'));
        }

        await Promise.all(promesas);

        // Configurar botones de idioma (si no están en el header)
        if (!window.languageButtonsInitialized) {
            document.querySelectorAll('[data-idioma]').forEach(button => {
                button.addEventListener('click', function(e) {
                    const idioma = this.getAttribute('data-idioma');
                    if (idioma) cambiarIdioma(idioma);
                });
            });
        }

        // Aplicar idioma inicial, sin escribir la cookie
        const idiomaGuardado = getCookie('idioma') || idiomaInicial;
        await cambiarIdioma(idiomaGuardado, true);

    } catch (error) {
        console.error("Error al inicializar las traducciones:", error);
    }
}

// Funciones de cookies
function setCookie(name, value, days = 30) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    const expires = "; expires=" + date.toUTCString();
    document.cookie = name + "=" + value + expires + "; path=/";
}

function getCookie(name) {
    const nameEQ = name + "=";
    const ca = document.cookie.split(';');
    for(let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return 'es';
}

// Exponer funciones necesarias globalmente
window.inicializarTraduccion = inicializarTraduccion;
window.cambiarIdioma = cambiarIdioma;