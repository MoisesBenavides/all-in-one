const TimeSlotHandler = {
    servicioSeleccionadoDuracion: 0,
    primerHorarioSeleccionado: null,

    generateTimeSlots() {
        const slots = [];
        // Genera horarios desde 5 AM hasta 5 PM en intervalos de 30 minutos
        for (let hour = 5; hour < 17; hour++) {
            slots.push(`${hour.toString().padStart(2, '0')}:00`);
            slots.push(`${hour.toString().padStart(2, '0')}:30`);
        }
        return slots;
    },

    async fetchBlockedTimes(selectedDate) {
        const loadingIndicator = document.getElementById('loadingIndicator');
        const timeSlotsContainer = document.getElementById('timeSlots');
        
        loadingIndicator.classList.remove('hidden');
        timeSlotsContainer.classList.add('hidden');
    
        try {
            console.log('Fetching blocked times for date:', selectedDate);
            
            const response = await fetch(`${GET_BLOCKED_TIMES_URL}?date=${selectedDate}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
    
            const data = await response.json();
            console.log('Raw response:', data);
    
            // Si no hay datos o no hay horariosTaller, devolver array vacío
            if (!data || !data.horariosTaller) {
                console.log('No blocked times found, returning empty array');
                return [];
            }
    
            // Asegurar que horariosTaller sea un array
            const horarios = Array.isArray(data.horariosTaller) ? data.horariosTaller : [];
            console.log('Processed blocked times:', horarios);
            
            return horarios;
        } catch (error) {
            console.error('Error fetching blocked times:', error);
            return [];
        } finally {
            loadingIndicator.classList.add('hidden');
            timeSlotsContainer.classList.remove('hidden');
        }
    },
    

    handleTimeSelection(time, button) {
        const horaInput = document.getElementById('hora_inicio');
        
        // Para servicios de 30 minutos o menos
        if (this.servicioSeleccionadoDuracion <= 30) {
            // Limpia selecciones previas
            document.querySelectorAll('#timeSlots button').forEach(btn => {
                btn.classList.remove('ring-2', 'ring-green-500', 'ring-yellow-500');
            });
            
            // Marca el horario seleccionado
            button.classList.add('ring-2', 'ring-green-500');
            
            // Guarda el horario
            horaInput.value = time;
            this.primerHorarioSeleccionado = null;
            
            // Muestra confirmación
            this.showConfirmation(`Horario seleccionado: ${time}`);
        } else {
            // Para servicios que requieren dos plazoss
            if (!this.primerHorarioSeleccionado) {
                // Primera selección
                document.querySelectorAll('#timeSlots button').forEach(btn => {
                    btn.classList.remove('ring-2', 'ring-green-500', 'ring-yellow-500');
                });
                this.primerHorarioSeleccionado = time;
                button.classList.add('ring-2', 'ring-green-500');
                
                // Resalta horarios adyacentes disponibles
                const timeIndex = this.generateTimeSlots().indexOf(time);
                const buttons = document.querySelectorAll('#timeSlots button');
                
                if (timeIndex > 0 && !buttons[timeIndex - 1].disabled) {
                    buttons[timeIndex - 1].classList.add('ring-2', 'ring-yellow-500');
                }
                if (timeIndex < buttons.length - 1 && !buttons[timeIndex + 1].disabled) {
                    buttons[timeIndex + 1].classList.add('ring-2', 'ring-yellow-500');
                }
            } else {
                // Segunda selección
                const firstIndex = this.generateTimeSlots().indexOf(this.primerHorarioSeleccionado);
                const secondIndex = this.generateTimeSlots().indexOf(time);
                
                if (Math.abs(firstIndex - secondIndex) === 1) {
                    document.querySelectorAll('#timeSlots button').forEach(btn => {
                        btn.classList.remove('ring-2', 'ring-green-500', 'ring-yellow-500');
                    });

                    const buttons = document.querySelectorAll('#timeSlots button');
                    buttons[Math.min(firstIndex, secondIndex)].classList.add('ring-2', 'ring-green-500');
                    buttons[Math.max(firstIndex, secondIndex)].classList.add('ring-2', 'ring-green-500');
                    
                    // Guarda el rango de horarios
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
        const timeSlotsContainer = document.getElementById('timeSlots');
        const serviceDurationMessage = document.getElementById('serviceDurationMessage');
    
        if (!this.servicioSeleccionadoDuracion) {
            this.showError('Por favor, seleccione un servicio antes de elegir el horario.');
            return;
        }
    
        try {
            // Inicializar blockedTimes como array vacío
            let blockedTimes = [];
            
            try {
                // Obtener horarios bloqueados con manejo de errores
                const fetchedTimes = await this.fetchBlockedTimes(selectedDate);
                // Asegurar que es un array
                blockedTimes = Array.isArray(fetchedTimes) ? fetchedTimes : [];
                console.log('Blocked times after fetch:', blockedTimes);
            } catch (error) {
                console.error('Error fetching blocked times:', error);
                // Mantener blockedTimes como array vacío en caso de error
            }
    
            const allTimeSlots = this.generateTimeSlots();
            console.log('All time slots:', allTimeSlots);
            
            timeSlotsContainer.innerHTML = '';
            serviceDurationMessage.classList.toggle('hidden', this.servicioSeleccionadoDuracion <= 30);
            
            allTimeSlots.forEach(time => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = time;
                
                // Verificación segura usando Array.prototype.includes
                const isBlocked = Array.isArray(blockedTimes) && blockedTimes.includes(time);
                let isAdjacentBlocked = false;
    
                if (this.servicioSeleccionadoDuracion > 30) {
                    const timeIndex = allTimeSlots.indexOf(time);
                    const nextTime = allTimeSlots[timeIndex + 1];
                    const prevTime = allTimeSlots[timeIndex - 1];
                    
                    // Verificación segura para horarios adyacentes
                    isAdjacentBlocked = Array.isArray(blockedTimes) && 
                        (nextTime && blockedTimes.includes(nextTime)) || 
                        (prevTime && blockedTimes.includes(prevTime));
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

window.TimeSlotHandler = TimeSlotHandler;