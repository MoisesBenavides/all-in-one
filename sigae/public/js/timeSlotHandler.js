const TimeSlotHandler = {
    servicioSeleccionadoDuracion: 0,    // Duración del servicio seleccionado
    primerHorarioSeleccionado: null,    // Almacena el primer horario en servicios largos

    // Genera los slots de tiempo disponibles del díaArray de strings con horarios en formato HH:mm
    
    generateTimeSlots() {
        const slots = [];
        // Genera horarios desde 5 AM hasta 5 PM en intervalos de 30 minutos
        for (let hour = 5; hour < 17; hour++) {
            slots.push(`${hour.toString().padStart(2, '0')}:00`);
            slots.push(`${hour.toString().padStart(2, '0')}:30`);
        }
        return slots;
    },

    // Obtiene los horarios bloqueados del servidor
   
    async fetchBlockedTimes(selectedDate) {
        const loadingIndicator = document.getElementById('loadingIndicator');
        const timeSlotsContainer = document.getElementById('timeSlots');
        
        // Muestra indicador de carga
        loadingIndicator.classList.remove('hidden');
        timeSlotsContainer.classList.add('hidden');

        try {
            const response = await fetch(`{{path('')}}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            return data.horariosOcupados;
        } catch (error) {
            console.error('Error al obtener horarios:', error);
            throw error;
        } finally {
            // Oculta indicador de carga
            loadingIndicator.classList.add('hidden');
            timeSlotsContainer.classList.remove('hidden');
        }
    },

    // Maneja la selección de horarios
     
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
            
            //Guarda el horario como array [hora seleccionada, null]
            const horarioSeleccionado = [time, null];
            horaInput.value = JSON.stringify(horarioSeleccionado);
            
            this.primerHorarioSeleccionado = null;
            
            // Muestra confirmación
            this.showConfirmation(`Horario seleccionado: ${time}`);
        } else {
            // Para servicios que requieren dos slots
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
                
                // Resalta horario anterior si está disponible
                if (timeIndex > 0 && !buttons[timeIndex - 1].disabled) {
                    buttons[timeIndex - 1].classList.add('ring-2', 'ring-yellow-500');
                }
                // Resalta horario siguiente si está disponible
                if (timeIndex < buttons.length - 1 && !buttons[timeIndex + 1].disabled) {
                    buttons[timeIndex + 1].classList.add('ring-2', 'ring-yellow-500');
                }
            } else {
                // Segunda selección
                const firstIndex = this.generateTimeSlots().indexOf(this.primerHorarioSeleccionado);
                const secondIndex = this.generateTimeSlots().indexOf(time);
                
                // Verifica que los horarios sean consecutivos
                if (Math.abs(firstIndex - secondIndex) === 1) {
                    // Limpia selecciones previas
                    document.querySelectorAll('#timeSlots button').forEach(btn => {
                        btn.classList.remove('ring-2', 'ring-green-500', 'ring-yellow-500');
                    });

                    // Marca ambos horarios seleccionados
                    const buttons = document.querySelectorAll('#timeSlots button');
                    buttons[Math.min(firstIndex, secondIndex)].classList.add('ring-2', 'ring-green-500');
                    buttons[Math.max(firstIndex, secondIndex)].classList.add('ring-2', 'ring-green-500');
                    
                    const tiempoInicio = Math.min(this.primerHorarioSeleccionado, time);
                    const tiempoFin = Math.max(this.primerHorarioSeleccionado, time);
                    
                    //Guarda los horarios como array [primera hora, segunda hora]
                    const horarioSeleccionado = [tiempoInicio, tiempoFin];
                    horaInput.value = JSON.stringify(horarioSeleccionado);
                    
                    this.primerHorarioSeleccionado = null;
                    
                    // Muestra confirmación con rango de horarios
                    this.showConfirmation(`Horario seleccionado: ${tiempoInicio} a ${tiempoFin}`);
                } else {
                    this.showError('Por favor, seleccione dos horarios consecutivos');
                    return;
                }
            }
        }
    },

    // Actualiza la visualización de horarios en ela UI
    async updateTimeSlots(selectedDate) {
        const timeSlotsContainer = document.getElementById('timeSlots');
        const serviceDurationMessage = document.getElementById('serviceDurationMessage');

        if (!this.servicioSeleccionadoDuracion) {
            this.showError('Por favor, seleccione un servicio antes de elegir el horario.');
            return;
        }

        try {
            const blockedTimes = await this.fetchBlockedTimes(selectedDate);
            const allTimeSlots = this.generateTimeSlots();
            
            timeSlotsContainer.innerHTML = '';
            serviceDurationMessage.classList.toggle('hidden', this.servicioSeleccionadoDuracion <= 30);
            
            allTimeSlots.forEach(time => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = time;
                
                const isBlocked = blockedTimes.includes(time);
                let isAdjacentBlocked = false;

                // Verifica horarios adyacentes (osea que estan al lado) para servicios largos
                if (this.servicioSeleccionadoDuracion > 30) {
                    const timeIndex = allTimeSlots.indexOf(time);
                    const nextTime = allTimeSlots[timeIndex + 1];
                    const prevTime = allTimeSlots[timeIndex - 1];
                    isAdjacentBlocked = (blockedTimes.includes(nextTime) && blockedTimes.includes(prevTime));
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
            console.error('Error:', error);
            this.showError('Error al actualizar los horarios.');
        }
    },

    // Muestra mensajes de error

    showError(message) {
        const errorContainer = document.getElementById('error-container');
        errorContainer.textContent = message;
        errorContainer.classList.remove('hidden');
        setTimeout(() => {
            errorContainer.classList.add('hidden');
        }, 5000);
    },


};

// Hacer disponible el objeto globalmente
window.TimeSlotHandler = TimeSlotHandler;