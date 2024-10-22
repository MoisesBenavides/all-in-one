/**
 * Inicializa y configura un input de fecha final que depende de una fecha inicial
 */
function initializeEndDate(inputId = 'fecha_final', startInputId = 'fecha_inicio') {
    // Obtener los elementos input
    const endInput = document.getElementById(inputId);
    const startInput = document.getElementById(startInputId);
    
    // Verificar que existan  
    if (!endInput || !startInput) {
        console.error(`Required inputs not found`);
        return;
    }
//formatear a formato de el elemnto date time local de hmtl
  
    function formatDateTime(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');  // Mes inicia en 0 por eso +1
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    
     // Actualiza las restricciones y valor del input de fecha final basándose en la fecha inicial
   
    function updateEndInput() {
        // Si no hay fecha inicial, no hay nada que actualizar asique nada...
        if (!startInput.value) return;
        
        // Calcular la fecha mínima permitida (30 minutos después de la fecha inicial)
        const startTime = new Date(startInput.value);
        const minEndTime = new Date(startTime);
        minEndTime.setMinutes(minEndTime.getMinutes() + 30);
        
        // Establecer restricciones en el input
        endInput.min = formatDateTime(minEndTime);    // Fecha mínima permitida
        endInput.step = "1800";                       // Intervalos de 30 minutos, en segs
        
        // Actualizar el valor si no es válido o es menor al mínimo permitido
        if (!endInput.value || new Date(endInput.value) < minEndTime) {
            endInput.value = formatDateTime(minEndTime);
        }
    }

    // Agregar listener para cambios manuales en la fecha final
    endInput.addEventListener('change', () => {
        // Verificar que exista una fecha inicial
        if (!startInput.value) return;
        
        // Verificar que la fecha final sea al menos 30 minutos después de la inicial
        const startTime = new Date(startInput.value);
        const endTime = new Date(endInput.value);
        const minEndTime = new Date(startTime.getTime() + 30 * 60000); // 30 minutos en milisegundos
        
        // Corregir si la fecha es menor a la mínima permitida
        if (endTime < minEndTime) {
            endInput.value = formatDateTime(minEndTime);
        }
    });

    // Escuchar cambios en la fecha de inicio para actualizar la fecha final
    document.addEventListener('startDateSelected', () => {
        updateEndInput();
    });

    // Realizar la configuración inicial
    updateEndInput();

    return {
        getCurrentEndDate: () => new Date(endInput.value),  // Obtener fecha final actual
        updateEndTime: updateEndInput                       // Actualizar manualmente
    };
}

// Ejemplo de cómo usar este código:
// const endDateHandler = initializeEndDate('tu_input_final_id', 'tu_input_inicio_id');