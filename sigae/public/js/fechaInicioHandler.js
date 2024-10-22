
function initializeStartDate(inputId = 'fecha_inicio') {

    const startInput = document.getElementById(inputId);
    
    // Verificar si se encontró el elemento input
    if (!startInput) {
        console.error(`Error: No se encontró el input con id ${inputId}`);
        return;
    }

    
     // Calcula la próxima hora válida disponible

     
    function getNextValidTime() {
        const now = new Date();
        const minutes = now.getMinutes();
        // Si los minutos son menos de 30, redondear a 30, sino a la siguiente hora
        const roundTo = minutes < 30 ? 30 : 60;
        
        // Configurar la fecha con los minutos calculados y resetear segundos y millisegundos
        now.setMinutes(roundTo);
        now.setSeconds(0);
        now.setMilliseconds(0);
        
        return now;
    }

    /**
     * Formatea una fecha al formato requerido por el input datetime-local
     * Convierte una fecha a formato: YYYY-MM-DDTHH:mm el que usa date time local de html predetrminado
   
     */
    function formatDateTime(date) {
        // Obtener cada componente de la fecha y asegurar formato de dos dígitos
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0'); // +1 porque los meses van de 0-11
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        
        // Retornar fecha formateada para el input datetime-local
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    /**
      Actualiza las restricciones y valor del input
      Se llama al inicializar y cada minuto
     */
    function updateStartInput() {
        // Obtener la próxima hora válida
        const minDateTime = getNextValidTime();
        // Establecer la fecha mínima permitida en el input
        startInput.min = formatDateTime(minDateTime);
        
        // Actualizar el valor solo si no hay uno válido o es menor al mínimo permitido
        if (!startInput.value || new Date(startInput.value) < minDateTime) {
            startInput.value = formatDateTime(minDateTime);
        }
        // Configurar el step para permitir solo intervalos de 30 minutos
        startInput.step = "1800"; // 1800 segundos = 30 minutos
    }

    // Agregar listener para cuando el usuario cambie la fecha manualmente
    startInput.addEventListener('change', () => {
        // Convertir la fecha seleccionada a el objeto Date
        const selectedDate = new Date(startInput.value);
        const minDate = getNextValidTime();
        
        // Si la fecha seleccionada es menor a la mínima permitida, corregirla
        if (selectedDate < minDate) {
            startInput.value = formatDateTime(minDate);
        }
        
        // Crear y disparar un evento personalizado para notificar cambios, se usa solo si es que hay una fecha final
        const event = new CustomEvent('startDateSelected', {
            detail: { startDate: new Date(startInput.value) }
        });
        document.dispatchEvent(event);
    });

    // Realizar la configuración inicial
    updateStartInput();
    
    // Configurar actualización automática cada minuto
    setInterval(updateStartInput, 60000); // 60000 ms = 1 minuto, duh

    // Retornar métodos públicos para usar externamente
    return {
        // Obtener la fecha actual seleccionada
        getCurrentStartDate: () => new Date(startInput.value),
        // Método para actualizar manualmente el input de fecha inicial
        updateStartTime: updateStartInput
    };
}

