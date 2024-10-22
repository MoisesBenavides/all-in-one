function initializeEndDate(inputId = 'fecha_final', startInputId = 'fecha_inicio') {
    //  ambos inputs del HTML
    const endInput = document.getElementById(inputId);         // Input de fecha final
    const startInput = document.getElementById(startInputId);  // Input de fecha inicial
    
    // Verificar que existan ambos inputs
    if (!endInput || !startInput) {
        console.error(`Error: No se encontraron los inputs necesarios`);
        return;
    }

    // Verifica si una fecha es anterior a la actual
    function isPastDate(date) {
        const now = new Date();
        return date < now;
    }

    // Convierte una fecha al formato que acepta el input datetime-local
    function formatDateTime(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');  // Mes + 1 porque enero es 0
        const day = String(date.getDate()).padStart(2, '0');         // Agrega 0 si es necesario
        const hours = String(date.getHours()).padStart(2, '0');      // Agrega 0 si es necesario
        const minutes = String(date.getMinutes()).padStart(2, '0');  // Agrega 0 si es necesario
        
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    // Redondea una fecha al próximo intervalo de 30 minutos
    function roundToNearestSlot(date) {
        const minutes = date.getMinutes();
        const hours = date.getHours();
        
        //  en el minuto 30 o menos, redondea a :30
        //  después del minuto 30, redondea a la próxima hora
        if (minutes <= 30) {
            date.setMinutes(30);
        } else {
            date.setHours(hours + 1);
            date.setMinutes(0);
        }
        
        // Limpiamos segundos y milisegundos
        date.setSeconds(0);
        date.setMilliseconds(0);

        // Si la fecha redondeada es pasada, avanzamos al siguiente slot
        if (isPastDate(date)) {
            date.setMinutes(date.getMinutes() + 30);
        }

        return date;
    }

    // Actualiza el input de fecha final basándose en la fecha inicial
    function updateEndInput() {
        // Si no hay fecha inicial, no puede funcionar
        if (!startInput.value) return;
        
        // Obtener la fecha inicial y calcular la mínima fecha final posible
        const startTime = new Date(startInput.value);
        const minEndTime = new Date(startTime);
        minEndTime.setMinutes(minEndTime.getMinutes() + 30); // 30 minutos después

        // Si la fecha mínima es pasada, avanzar al siguiente slot disponible
        if (isPastDate(minEndTime)) {
            minEndTime.setMinutes(minEndTime.getMinutes() + 30);
        }
        
        // Establecer restricciones en el input
        endInput.min = formatDateTime(minEndTime);  // Fecha mínima permitida
        endInput.step = "1800";                     // Intervalos de 30 minutos
        
        // Si no hay fecha seleccionada o es menor que la mínima permitida o es pasada,
        // establecer la fecha mínima como valor
        if (!endInput.value || 
            new Date(endInput.value) < minEndTime || 
            isPastDate(new Date(endInput.value))) {
            endInput.value = formatDateTime(minEndTime);
        }
    }

    // Este evento se dispara cuando el usuario cambia la fecha final manualmente
    endInput.addEventListener('change', () => {
        // Verificar que exista una fecha inicial
        if (!startInput.value) return;
        
        // Obtener y validar las fechas
        const startTime = new Date(startInput.value);
        const endTime = new Date(endInput.value);
        const minEndTime = new Date(startTime.getTime() + 30 * 60000); // 30 minutos en ms
        
        // Redondear la fecha seleccionada al intervalo de 30 minutos más cercano
        const roundedDate = roundToNearestSlot(endTime);
        
        // Si la fecha es pasada o menor a la mínima permitida, usar la mínima
        if (isPastDate(roundedDate) || roundedDate < minEndTime) {
            endInput.value = formatDateTime(minEndTime);
            alert("La fecha final debe ser al menos 30 minutos después de la fecha inicial y no puede ser pasada.");
        } else {
            endInput.value = formatDateTime(roundedDate);
        }
    });

    // Escuchar cambios en la fecha inicial para actualizar la fecha final
    document.addEventListener('startDateSelected', () => {
        updateEndInput();
    });

    // Configuración inicial
    updateEndInput();

    return {
        getCurrentEndDate: () => new Date(endInput.value),  // Obtener la fecha final actual
        updateEndTime: updateEndInput                       // Forzar una actualización
    };
}