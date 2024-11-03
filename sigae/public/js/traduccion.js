// Variables globales
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
        console.log(`Intentando cargar archivo ${tipo}:`, archivo);
        const response = await fetch(archivo);
        
        if (!response.ok) {
            console.error(`Error al cargar archivo ${tipo}: HTTP ${response.status}`);
            throw new Error(`Error HTTP ${response.status}`);
        }
        
        const data = await response.json();
        console.log(`Archivo ${tipo} cargado correctamente`);
        
        if (tipo === 'header') {
            traduccionesHeader = data;
        } else if (tipo === 'vista' || tipo === 'unico') {
            traduccionesVista = data;
        }
        
        return data;
    } catch (error) {
        if (error.name === 'SyntaxError') {
            console.error(`Error de sintaxis en archivo ${tipo}: El archivo no es un JSON válido`);
        } else {
            console.error(`Error al cargar traducciones de ${tipo}:`, error);
        }
        return {};
    }
}

// Función para aplicar las traducciones
function aplicarTraducciones(traducciones, tipo) {
    if (!traducciones) {
        console.warn(`No hay traducciones disponibles para ${tipo}`);
        return;
    }

    let elementosTraducidos = 0;
    const elementosNoTraducidos = [];

    document.querySelectorAll('[traducir]').forEach(elemento => {
        const clave = elemento.getAttribute('traducir');
        if (traducciones[clave]) {
            if (elemento.tagName === 'INPUT') {
                elemento.placeholder = traducciones[clave];
            } else {
                elemento.textContent = traducciones[clave];
            }
            elementosTraducidos++;
        } else {
            elementosNoTraducidos.push(clave);
        }
    });

    console.log(`${elementosTraducidos} elementos traducidos para ${tipo}`);
    if (elementosNoTraducidos.length > 0) {
        console.warn(`Claves no encontradas en ${tipo}:`, elementosNoTraducidos);
    }
}

// Función para cambiar el idioma
async function cambiarIdioma(idioma, skipCookie = false) {
    console.log(`Cambiando idioma a: ${idioma}`);
    try {
        // Usar las traducciones ya cargadas
        if (traduccionesHeader[idioma]) {
            aplicarTraducciones(traduccionesHeader[idioma], 'header');
        }
        if (traduccionesVista[idioma]) {
            aplicarTraducciones(traduccionesVista[idioma], 'vista');
        }
        
        if (!skipCookie) {
            setCookie('idioma', idioma, 30);
            console.log(`Idioma ${idioma} guardado en cookie`);
        }
    } catch (error) {
        console.error('Error al cambiar idioma:', error);
    }
}

// Función para inicializar la traducción
async function inicializarTraduccion(archivoHeader = null, archivoVista = null, idiomaInicial = 'es') {
    console.log('Iniciando sistema de traducciones');
    console.log('Archivo header:', archivoHeader);
    console.log('Archivo vista:', archivoVista);
    
    try {
        rutaArchivoHeader = archivoHeader;
        rutaArchivoVista = archivoVista;

        // Cargar las traducciones iniciales
        const promesas = [];
        
        if (archivoHeader) {
            promesas.push(cargarTraducciones(archivoHeader, 'header'));
        }
        
        if (archivoVista) {
            promesas.push(cargarTraducciones(archivoVista, 'vista'));
        }

        await Promise.all(promesas);

        // Configurar botones de idioma
        if (!window.languageButtonsInitialized) {
            const botones = document.querySelectorAll('[data-idioma]');
            botones.forEach(button => {
                button.addEventListener('click', function(e) {
                    const idioma = this.getAttribute('data-idioma');
                    if (idioma) cambiarIdioma(idioma);
                });
            });
            window.languageButtonsInitialized = true;
            console.log(`${botones.length} botones de idioma inicializados`);
        }

        // Aplicar idioma inicial
        const idiomaGuardado = getCookie('idioma') || idiomaInicial;
        console.log(`Aplicando idioma inicial: ${idiomaGuardado}`);
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