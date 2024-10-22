
function initializeStartDate(inputId = 'fecha_inicio') {
    //  Obtener el elemento input del DOM osea del HTML
    const startInput = document.getElementById(inputId);
    
    // Verificar si existe el input
    if (!startInput) {
        console.error(`Error: No se encontró el input con id ${inputId}`);
        return;
    }

    // Esta función redondea una fecha a la próxima media hora o hora en punto
    function roundToNearestSlot(date) {
        const minutes = date.getMinutes();
        const hours = date.getHours();
        
        //  en el minuto 30 o menos, redondea a :30
        // después del minuto 30, redondea a la próxima hora
        if (minutes <= 30) {
            date.setMinutes(30);
        } else {
            date.setHours(hours + 1);
            date.setMinutes(0);
        }
        
        // Limpiamos segundos y milisegundos para tener una hora exacta
        date.setSeconds(0);
        date.setMilliseconds(0);
        return date;
    }

    // Obtiene la próxima hora válida disponible para reservar
    function getNextValidTime() {
        const now = new Date();
        return roundToNearestSlot(now);
    }

    // Convierte una fecha a el formato que acepta el input datetime-local
    // El formato es: YYYY-MM-DDTHH:mm
    function formatDateTime(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');  // Mes + 1 porque enero es 0
        const day = String(date.getDate()).padStart(2, '0');         // Agrega 0 si es necesario
        const hours = String(date.getHours()).padStart(2, '0');      // Agrega 0 si es necesario
        const minutes = String(date.getMinutes()).padStart(2, '0');  // Agrega 0 si es necesario
        
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    // Actualiza el input con las restricciones y valores correctos
    function updateStartInput() {
        // Obtener la próxima hora válida
        const minDateTime = getNextValidTime();
        
        // Establecer la fecha mínima que se puede seleccionar
        startInput.min = formatDateTime(minDateTime);
        
        // Si no hay fecha seleccionada o es menor que la mínima permitida, establecer la fecha mínima como valor
        if (!startInput.value || new Date(startInput.value) < minDateTime) {
            startInput.value = formatDateTime(minDateTime);
        }
        
        // Configurar el input para que solo permita intervalos de 30 minutos
        startInput.step = "1800"; // 1800 segundos = 30 minutos duh
    }

    // Este evento se dispara cuando el usuario cambia la fecha manualmente
    startInput.addEventListener('change', () => {
        // Convertir el valor seleccionado a objeto Date
        const selectedDate = new Date(startInput.value);
        const minDate = getNextValidTime();
        
        // Redondear la fecha seleccionada al intervalo de 30 minutos más cercano
        const roundedDate = roundToNearestSlot(selectedDate);
        
        // Si la fecha redondeada es menor que la mínima permitida,
        // usar la fecha mínima en su lugar
        if (roundedDate < minDate) {
            startInput.value = formatDateTime(minDate);
        } else {
            startInput.value = formatDateTime(roundedDate);
        }
        
        // Avisar a otros componentes (osea la fecha final) que hubo un cambio
        const event = new CustomEvent('startDateSelected', {
            detail: { startDate: new Date(startInput.value) }
        });
        document.dispatchEvent(event);
    });

    // Configuración inicial
    updateStartInput();
    
    // Actualizar cada minuto para mantener las fechas válidas
    setInterval(updateStartInput, 60000); // 60000 ms = 1 minuto

    // Funciones que otros componentes pueden usar
    return {
        getCurrentStartDate: () => new Date(startInput.value),  // Obtener la fecha actual
        updateStartTime: updateStartInput                       // Forzar una actualización
    };
}