    // Verificar que la URL esté definida
    if (typeof GET_BLOCKED_TIMES_URL === 'undefined') {
        console.error('GET_BLOCKED_TIMES_URL no está definida');
    }

    const TimeSlotHandler = {
        servicioSeleccionadoDuracion: 0,
        primerHorarioSeleccionado: null,

        generateTimeSlots() {
            const slots = [];
            for (let hour = 5; hour < 17; hour++) {
                slots.push(`${hour.toString().padStart(2, '0')}:00`);
                slots.push(`${hour.toString().padStart(2, '0')}:30`);
            }
            return slots;
        },

        async fetchBlockedTimes(selectedDate) {
            if (!selectedDate) {
                console.error('No se proporcionó fecha');
                return [];
            }

            const loadingIndicator = document.getElementById('loadingIndicator');
            const timeSlotsContainer = document.getElementById('timeSlots');
            
            if (!loadingIndicator || !timeSlotsContainer) {
                console.error('No se encontraron elementos DOM necesarios');
                return [];
            }

            loadingIndicator.classList.remove('hidden');
            timeSlotsContainer.classList.add('hidden');
        
            try {
                console.log('URL de fetch:', `${GET_BLOCKED_TIMES_URL}?date=${selectedDate}`);
                console.log('Iniciando fetch para fecha:', selectedDate);
                
                const response = await fetch(`${GET_BLOCKED_TIMES_URL}?date=${selectedDate}`);
                console.log('Respuesta recibida:', response);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
        
                const data = await response.json();
                console.log('Datos recibidos:', data);
        
                if (!data) {
                    console.log('No se recibieron datos');
                    return [];
                }

                if (!data.horariosTaller) {
                    console.log('No hay horariosTaller en los datos');
                    return [];
                }
        
                const horarios = Array.isArray(data.horariosTaller) ? data.horariosTaller : [];
                console.log('Horarios procesados:', horarios);
                
                return horarios;
            } catch (error) {
                console.error('Error completo:', error);
                return [];
            } finally {
                loadingIndicator.classList.add('hidden');
                timeSlotsContainer.classList.remove('hidden');
            }
        },

        handleTimeSelection(time, button) {
            const horaInput = document.getElementById('hora_inicio');
            
            if (this.servicioSeleccionadoDuracion <= 30) {
                document.querySelectorAll('#timeSlots button').forEach(btn => {
                    btn.classList.remove('ring-2', 'ring-green-500', 'ring-yellow-500');
                });
                
                button.classList.add('ring-2', 'ring-green-500');
                horaInput.value = time;
                this.primerHorarioSeleccionado = null;
                this.showConfirmation(`Horario seleccionado: ${time}`);
            } else {
                if (!this.primerHorarioSeleccionado) {
                    document.querySelectorAll('#timeSlots button').forEach(btn => {
                        btn.classList.remove('ring-2', 'ring-green-500', 'ring-yellow-500');
                    });
                    this.primerHorarioSeleccionado = time;
                    button.classList.add('ring-2', 'ring-green-500');
                    
                    const timeIndex = this.generateTimeSlots().indexOf(time);
                    const buttons = document.querySelectorAll('#timeSlots button');
                    
                    if (timeIndex > 0 && !buttons[timeIndex - 1].disabled) {
                        buttons[timeIndex - 1].classList.add('ring-2', 'ring-yellow-500');
                    }
                    if (timeIndex < buttons.length - 1 && !buttons[timeIndex + 1].disabled) {
                        buttons[timeIndex + 1].classList.add('ring-2', 'ring-yellow-500');
                    }
                } else {
                    const firstIndex = this.generateTimeSlots().indexOf(this.primerHorarioSeleccionado);
                    const secondIndex = this.generateTimeSlots().indexOf(time);
                    
                    if (Math.abs(firstIndex - secondIndex) === 1) {
                        document.querySelectorAll('#timeSlots button').forEach(btn => {
                            btn.classList.remove('ring-2', 'ring-green-500', 'ring-yellow-500');
                        });

                        const buttons = document.querySelectorAll('#timeSlots button');
                        buttons[Math.min(firstIndex, secondIndex)].classList.add('ring-2', 'ring-green-500');
                        buttons[Math.max(firstIndex, secondIndex)].classList.add('ring-2', 'ring-green-500');
                        
                        horaInput.value = JSON.stringify([
                            Math.min(this.primerHorarioSeleccionado, time),
                            Math.max(this.primerHorarioSeleccionado, time)
                        ]);
                        
                        this.primerHorarioSeleccionado = null;
                    } else {
                        this.showError('Por favor, seleccione dos horarios consecutivos');
                        return;
                    }
                }
            }
        },

    
        async updateTimeSlots(selectedDate) {
            console.log('Iniciando updateTimeSlots con fecha:', selectedDate);
            
            const timeSlotsContainer = document.getElementById('timeSlots');
            const serviceDurationMessage = document.getElementById('serviceDurationMessage');
    
            if (!timeSlotsContainer || !serviceDurationMessage) {
                console.error('Elementos DOM no encontrados');
                return;
            }
        
            if (!this.servicioSeleccionadoDuracion) {
                console.log('No hay duración de servicio seleccionada');
                this.showError('Por favor, seleccione un servicio antes de elegir el horario.');
                return;
            }
        
            try {
                // Inicializar blockedTimes como array vacío
                let blockedTimes = [];
                
                try {
                    const response = await this.fetchBlockedTimes(selectedDate);
                    console.log('Respuesta de fetchBlockedTimes:', response);
                    
                    // Asegurarnos de que blockedTimes sea siempre un array
                    if (Array.isArray(response)) {
                        blockedTimes = response;
                    } else {
                        console.warn('fetchBlockedTimes no devolvió un array:', response);
                    }
                } catch (error) {
                    console.error('Error fetching blocked times:', error);
                }
    
                console.log('BlockedTimes después de fetch:', blockedTimes);
                const allTimeSlots = this.generateTimeSlots();
                console.log('AllTimeSlots generados:', allTimeSlots);
                
                timeSlotsContainer.innerHTML = '';
                serviceDurationMessage.classList.toggle('hidden', this.servicioSeleccionadoDuracion <= 30);
                
                // Verificar que blockedTimes sea un array antes de usarlo
                if (!Array.isArray(blockedTimes)) {
                    console.warn('BlockedTimes no es un array, inicializando como vacío');
                    blockedTimes = [];
                }
    
                allTimeSlots.forEach(time => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = time;
                    
                    // Verificación segura de includes
                    const isBlocked = blockedTimes.includes?.(time) ?? false;
                    let isAdjacentBlocked = false;
    
                    if (this.servicioSeleccionadoDuracion > 30) {
                        const timeIndex = allTimeSlots.indexOf(time);
                        const nextTime = allTimeSlots[timeIndex + 1];
                        const prevTime = allTimeSlots[timeIndex - 1];
                        
                        // Verificación segura para horarios adyacentes
                        isAdjacentBlocked = 
                            (nextTime && blockedTimes.includes?.(nextTime)) || 
                            (prevTime && blockedTimes.includes?.(prevTime)) || 
                            false;
                    }
    
                    const isDisabled = isBlocked || isAdjacentBlocked;
                    
                    button.className = isDisabled 
                        ? 'p-2 rounded-md text-center bg-red-100 text-red-800 cursor-not-allowed'
                        : 'p-2 rounded-md text-center bg-green-100 text-green-800 hover:bg-green-200';
                    
                    if (!isDisabled) {
                        button.addEventListener('click', () => this.handleTimeSelection(time, button));
                    }
                    
                    button.disabled = isDisabled;
                    timeSlotsContainer.appendChild(button);
                });
            } catch (error) {
                console.error('Error en updateTimeSlots:', error);
                this.showError('Error al actualizar los horarios.');
            }
        },


    showError(message) {
        const errorContainer = document.getElementById('error-container');
        errorContainer.textContent = message;
        errorContainer.classList.remove('hidden');
        setTimeout(() => {
            errorContainer.classList.add('hidden');
        }, 5000);
    },

    showConfirmation(message) {
        console.log(message);
    }
};

// Verificar que TimeSlotHandler se exportó correctamente
console.log('TimeSlotHandler inicializado:', !!window.TimeSlotHandler);

window.TimeSlotHandler = TimeSlotHandler;

// Verificar inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Cargado');
    console.log('URL configurada:', GET_BLOCKED_TIMES_URL);
    console.log('TimeSlotHandler disponible:', !!window.TimeSlotHandler);
});