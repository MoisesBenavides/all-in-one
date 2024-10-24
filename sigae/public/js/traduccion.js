// Variables globales para almacenar las traducciones
let traduccionesHeader = {};
let traduccionesVista = {};

// Funciones para manejar cookies
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
    return 'es'; // Valor por defecto si no existe la cookie
}

// Función para cargar las traducciones
async function cargarTraducciones(archivo, tipo) {
    if (!archivo) {
        console.error(`Ruta de archivo no válida para ${tipo}`);
        return {};
    }

    try {
        const response = await fetch(archivo);
        if (!response.ok) {
            throw new Error(`Error HTTP ${response.status}`);
        }
        const data = await response.json();
        
        if (tipo === 'header') {
            traduccionesHeader = data;
        } else if (tipo === 'vista') {
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
        }
    });
}

// Función para cambiar el idioma
function cambiarIdioma(idioma) {
    console.log('Cambiando idioma a:', idioma);
    
    // Actualizar displays de idioma
    const currentLanguage = document.getElementById('currentLanguage');
    const currentLanguageDesktop = document.getElementById('currentLanguageDesktop');
    
    if (currentLanguage) {
        currentLanguage.textContent = idioma.toUpperCase();
    }
    if (currentLanguageDesktop) {
        currentLanguageDesktop.textContent = idioma.toUpperCase();
    }

    // Aplicar traducciones
    if (traduccionesHeader[idioma]) {
        aplicarTraducciones(traduccionesHeader[idioma], 'header');
    }
    if (traduccionesVista[idioma]) {
        aplicarTraducciones(traduccionesVista[idioma], 'vista');
    }
    
    setCookie('idioma', idioma);
}

// Función para inicializar la traducción
async function inicializarTraduccion(archivoHeader, archivoVista, idiomaInicial = 'es') {
    try {
        // Cargar las traducciones
        await Promise.all([
            cargarTraducciones(archivoHeader, 'header'),
            cargarTraducciones(archivoVista, 'vista')
        ]);

        // Configurar los botones de idioma
        document.querySelectorAll('[data-idioma]').forEach(button => {
            button.addEventListener('click', (e) => {
                const idioma = e.currentTarget.getAttribute('data-idioma');
                cambiarIdioma(idioma);
            });
        });

        // Aplicar el idioma inicial o guardado
        const idiomaGuardado = getCookie('idioma');
        cambiarIdioma(idiomaGuardado || idiomaInicial);
        
    } catch (error) {
        console.error("Error al inicializar las traducciones:", error);
    }
}

// Exponer las funciones necesarias globalmente
window.inicializarTraduccion = inicializarTraduccion;
window.cambiarIdioma = cambiarIdioma;