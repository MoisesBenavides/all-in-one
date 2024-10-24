// Variables globales para almacenar las traducciones
let traduccionesHeader = {};
let traduccionesVista = {};

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
        console.log(`Traducciones cargadas para ${tipo}:`, data);
        
        /*
        if (tipo === 'header') {
            traduccionesHeader = data;
        } else if (tipo === 'vista') {
            traduccionesVista = data;
        } else if (tipo === 'unico') {
            // Para páginas como la landing que solo tienen un archivo
            traduccionesVista = data;
        }
            */
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

    // Actualizar UI si existe
    const currentLanguage = document.getElementById('currentLanguage');
    const currentLanguageDesktop = document.getElementById('currentLanguageDesktop');
    
    if (currentLanguage) currentLanguage.textContent = idioma.toUpperCase();
    if (currentLanguageDesktop) currentLanguageDesktop.textContent = idioma.toUpperCase();

    // Aplicar traducciones
    if (traduccionesHeader[idioma]) {
        aplicarTraducciones(traduccionesHeader[idioma], 'header');
    }
    if (traduccionesVista[idioma]) {
        aplicarTraducciones(traduccionesVista[idioma], 'vista');
    }
    
    setCookie('idioma', idioma, 30);
}

// Función para inicializar la traducción
async function inicializarTraduccion(archivoHeader = null, archivoVista = null, idiomaInicial = 'es') {
    try {
        if (archivoVista === null && archivoHeader !== null) {
            // Caso landing page: solo un archivo
            await cargarTraducciones(archivoHeader, 'unico');
        } else {
            // Caso páginas con header: dos archivos
            const promesas = [];
            if (archivoHeader) promesas.push(cargarTraducciones(archivoHeader, 'header'));
            if (archivoVista) promesas.push(cargarTraducciones(archivoVista, 'vista'));
            await Promise.all(promesas);
        }

        // Configurar botones de idioma
        document.querySelectorAll('[data-idioma]').forEach(button => {
            button.removeEventListener('click', handleLanguageClick);
            button.addEventListener('click', handleLanguageClick);
        });

        // Aplicar idioma inicial
        const idiomaGuardado = getCookie('idioma');
        cambiarIdioma(idiomaGuardado || idiomaInicial);
        
    } catch (error) {
        console.error("Error al inicializar las traducciones:", error);
    }
}

// Manejador de eventos para botones de idioma
function handleLanguageClick(e) {
    const idioma = e.currentTarget.getAttribute('data-idioma');
    cambiarIdioma(idioma);
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

// Exponer las funciones necesarias globalmente
window.inicializarTraduccion = inicializarTraduccion;
window.cambiarIdioma = cambiarIdioma;