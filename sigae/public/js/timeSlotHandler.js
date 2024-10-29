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
        const loadingIndicator = document.getElementById('loadingIndicator');
        const timeSlotsContainer = document.getElementById('timeSlots');
        
        if (loadingIndicator) loadingIndicator.classList.remove('hidden');
        if (timeSlotsContainer) timeSlotsContainer.classList.add('hidden');
    
        try {
            const response = await fetch(`${GET_BLOCKED_TIMES_URL}?date=${selectedDate}`);
            const data = await response.json();
            return data.horariosTaller || [];
        } catch {
            return [];
        } finally {
            if (loadingIndicator) loadingIndicator.classList.add('hidden');
            if (timeSlotsContainer) timeSlotsContainer.classList.remove('hidden');
        }
    },

    formatDateTime(dateStr, timeStr) {
        try {
            // Convertir la fecha a objeto Date
            const date = new Date(dateStr + 'T00:00:00'); // Añadir tiempo para evitar problemas con zonas horarias
            
            // Extraer componentes de la hora
            const [hours, minutes] = timeStr.split(':');

            // Construir la fecha formateada
            const formattedDate = date.getFullYear() + '-' +
                                String(date.getMonth() + 1).padStart(2, '0') + '-' +
                                String(date.getDate()).padStart(2, '0');

            // Construir la hora formateada
            const formattedTime = String(hours).padStart(2, '0') + ':' + 
                                String(minutes).padStart(2, '0');

            // Combinar en el formato requerido
            return `${formattedDate}T${formattedTime}`;
        } catch (error) {
            console.error('Error formateando fecha y hora:', error);
            return '';
        }
    },

    handleTimeSelection(time, button) {
        const fechaInput = document.getElementById('fecha_inicio');
        const datePicker = document.getElementById('fecha_selector');
        
        if (!datePicker.value) {
            this.showError('Por favor, seleccione primero una fecha.');
            return;
        }
        
        if (this.servicioSeleccionadoDuracion <= 30) {
            document.querySelectorAll('#timeSlots button').forEach(btn => {
                btn.classList.remove('bg-red-600', 'text-white');
            });
            
            button.classList.add('bg-red-600', 'text-white');
            
            // Formatear fecha y hora
            const formattedDateTime = this.formatDateTime(datePicker.value, time);
            console.log('Fecha formateada:', formattedDateTime); // Debug log
            
            if (!formattedDateTime) {
                this.showError('Error al formatear la fecha y hora.');
                return;
            }
            
            fechaInput.value = formattedDateTime;
            this.primerHorarioSeleccionado = null;
            
        } else {
            if (!this.primerHorarioSeleccionado) {
                document.querySelectorAll('#timeSlots button').forEach(btn => {
                    btn.classList.remove('bg-red-600', 'text-white', 'bg-yellow-200');
                });
                
                this.primerHorarioSeleccionado = time;
                button.classList.add('bg-red-600', 'text-white');
                
                const timeIndex = this.generateTimeSlots().indexOf(time);
                const buttons = document.querySelectorAll('#timeSlots button');
                
                if (timeIndex > 0 && !buttons[timeIndex - 1].disabled) {
                    buttons[timeIndex - 1].classList.add('bg-yellow-200');
                }
                if (timeIndex < buttons.length - 1 && !buttons[timeIndex + 1].disabled) {
                    buttons[timeIndex + 1].classList.add('bg-yellow-200');
                }
            } else {
                const firstIndex = this.generateTimeSlots().indexOf(this.primerHorarioSeleccionado);
                const secondIndex = this.generateTimeSlots().indexOf(time);
                
                if (Math.abs(firstIndex - secondIndex) === 1) {
                    document.querySelectorAll('#timeSlots button').forEach(btn => {
                        btn.classList.remove('bg-red-600', 'text-white', 'bg-yellow-200');
                    });

                    const buttons = document.querySelectorAll('#timeSlots button');
                    buttons[Math.min(firstIndex, secondIndex)].classList.add('bg-red-600', 'text-white');
                    buttons[Math.max(firstIndex, secondIndex)].classList.add('bg-red-600', 'text-white');
                    
                    // Usar el primer horario
                    const slots = this.generateTimeSlots();
                    const primerHorario = slots[Math.min(firstIndex, secondIndex)];
                    
                    // Formatear fecha y hora
                    const formattedDateTime = this.formatDateTime(datePicker.value, primerHorario);
                    console.log('Fecha formateada (servicio largo):', formattedDateTime); // Debug log
                    
                    if (!formattedDateTime) {
                        this.showError('Error al formatear la fecha y hora.');
                        return;
                    }
                    
                    fechaInput.value = formattedDateTime;
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

        if (!timeSlotsContainer || !this.servicioSeleccionadoDuracion) {
            this.showError('Por favor, seleccione un servicio antes de elegir el horario.');
            return;
        }
    
        try {
            const blockedTimes = await this.fetchBlockedTimes(selectedDate);
            const blockedTimesMap = {};
            blockedTimes.forEach(time => blockedTimesMap[time] = true);

            const allTimeSlots = this.generateTimeSlots();
            timeSlotsContainer.innerHTML = '';
            if (serviceDurationMessage) {
                serviceDurationMessage.classList.toggle('hidden', this.servicioSeleccionadoDuracion <= 30);
            }
            
            allTimeSlots.forEach(time => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = time;
                
                const isBlocked = blockedTimesMap[time];
                let isAdjacentBlocked = false;

                if (this.servicioSeleccionadoDuracion > 30) {
                    const timeIndex = allTimeSlots.indexOf(time);
                    const nextTime = allTimeSlots[timeIndex + 1];
                    const prevTime = allTimeSlots[timeIndex - 1];
                    isAdjacentBlocked = 
                        (nextTime && blockedTimesMap[nextTime]) || 
                        (prevTime && blockedTimesMap[prevTime]);
                }

                const isDisabled = isBlocked || isAdjacentBlocked;
                
                button.className = `w-full p-2 rounded-md text-center transition-colors ${
                    isDisabled 
                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                        : 'bg-white border border-gray-300 hover:bg-gray-50'
                }`;
                
                if (!isDisabled) {
                    button.addEventListener('click', () => this.handleTimeSelection(time, button));
                }
                
                button.disabled = isDisabled;
                timeSlotsContainer.appendChild(button);
            });
        } catch {
            this.showError('Error al actualizar los horarios.');
        }
    },

    showError(message) {
        const errorContainer = document.getElementById('error-container');
        if (errorContainer) {
            errorContainer.classList.remove('hidden');
            const errorList = document.getElementById('error-list');
            if (errorList) {
                errorList.innerHTML = `<li>${message}</li>`;
            } else {
                errorContainer.textContent = message;
            }
            setTimeout(() => errorContainer.classList.add('hidden'), 5000);
        }
    }
};


window.TimeSlotHandler = TimeSlotHandler;