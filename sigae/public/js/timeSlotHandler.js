console.log("debug");
const TimeSlotHandler = {
    servicioSeleccionadoDuracion: 0,
    primerHorarioSeleccionado: null,

    debug(message, data = null) {
        const timestamp = new Date().toISOString();
        console.log(`[${timestamp}] TimeSlotHandler: ${message}`);
        if (data) {
            console.log('Data:', data);
        }
    },

    getLocalDate(date) {
        const d = new Date(date);
        // Ajustar a la zona horaria local de Uruguay
        const uruguayDate = new Date(d.toLocaleString('en-US', { timeZone: 'America/Montevideo' }));
        return new Date(uruguayDate.getFullYear(), uruguayDate.getMonth(), uruguayDate.getDate());
    },

    updateTimeSlots(selectedDate) {
        this.debug('Iniciando updateTimeSlots', { selectedDate });
        
        const timeSlotsContainer = document.getElementById('timeSlots');
        const serviceDurationMessage = document.getElementById('serviceDurationMessage');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const errorContainer = document.getElementById('error-container');
    
        // Validar fecha
        try {
            const selectedLocalDate = this.getLocalDate(selectedDate);
            const todayLocalDate = this.getLocalDate(new Date());
            
            this.debug('Fechas locales', {
                selectedLocalDate,
                todayLocalDate,
                selectedTimestamp: selectedLocalDate.getTime(),
                todayTimestamp: todayLocalDate.getTime()
            });
    
            // Comparar solo las fechas sin la hora
            const selectedDateOnly = new Date(selectedLocalDate.setHours(0,0,0,0));
            const todayDateOnly = new Date(todayLocalDate.setHours(0,0,0,0));
    
            if (selectedDateOnly.getTime() < todayDateOnly.getTime()) {
                throw new Error('No se pueden seleccionar fechas pasadas');
            }
        } catch (error) {
            this.debug('Error en validación de fecha', { error: error.message });
            this.showError(error.message);
            return Promise.reject(error);
        }
        // Validaciones iniciales
        if (!timeSlotsContainer) {
            this.debug('Error: No se encontró el contenedor de time slots');
            return Promise.reject(new Error('Error de configuración: contenedor no encontrado'));
        }

        if (!this.servicioSeleccionadoDuracion) {
            this.debug('Error: No se ha seleccionado un servicio');
            this.showError('Por favor, seleccione un servicio antes de elegir el horario.');
            return Promise.reject(new Error('No se ha seleccionado un servicio'));
        }

        // Mostrar loading y limpiar estado
        if (loadingIndicator) loadingIndicator.classList.remove('hidden');
        if (errorContainer) errorContainer.classList.add('hidden');

        this.resetState(timeSlotsContainer);

        // Construir y validar URL
        let requestUrl;
        try {
            requestUrl = this.buildUrl(selectedDate);
            this.debug('URL construida', { url: requestUrl });
        } catch (error) {
            this.debug('Error construyendo URL', { error: error.message });
            this.showError('Error al procesar la fecha');
            return Promise.reject(error);
        }

        // Hacer la petición
        return fetch(requestUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include'
        })
        .then(async response => {
            this.debug('Respuesta recibida', {
                status: response.status,
                statusText: response.statusText,
                headers: Object.fromEntries(response.headers.entries())
            });

            if (!response.ok) {
                const errorData = await response.text();
                this.debug('Error en respuesta', { 
                    status: response.status,
                    errorData 
                });

                try {
                    const parsedError = JSON.parse(errorData);
                    throw new Error(parsedError.error || 'Error al obtener los horarios');
                } catch (e) {
                    throw new Error(`Error ${response.status}: ${errorData || response.statusText}`);
                }
            }

            return response.json();
        })
        .then(data => {
            this.debug('Datos recibidos', { data });

            if (!data || !data.horariosTaller) {
                throw new Error('Formato de respuesta inválido');
            }

            const slots = Object.entries(data.horariosTaller);
            this.debug('Slots procesados', { 
                totalSlots: slots.length,
                firstSlot: slots[0],
                lastSlot: slots[slots.length - 1]
            });

            return this.renderTimeSlots(slots, selectedDate, serviceDurationMessage);
        })
        .catch(error => {
            this.debug('Error capturado', {
                message: error.message,
                stack: error.stack
            });
            this.showError(error.message);
            timeSlotsContainer.innerHTML = '';
            throw error;
        })
        .finally(() => {
            if (loadingIndicator) loadingIndicator.classList.add('hidden');
        });
    },

    buildUrl(selectedDate) {
        // Formatear fecha como YYYY-MM-DD usando la fecha local
        const date = new Date(selectedDate);
        const formattedDate = [
            date.getFullYear(),
            String(date.getMonth() + 1).padStart(2, '0'),
            String(date.getDate()).padStart(2, '0')
        ].join('-');

        this.debug('Fecha formateada para URL', {
            input: selectedDate,
            formatted: formattedDate
        });
        
        return `${GET_BLOCKED_TIMES_URL}?date=${encodeURIComponent(formattedDate)}`;
    },


    resetState(container) {
        this.debug('Reseteando estado');
        this.primerHorarioSeleccionado = null;
        const fechaInicio = document.getElementById('fecha_inicio');
        if (fechaInicio) fechaInicio.value = '';
        container.innerHTML = '';
    },

    formatDateForPHP(date) {
        const d = new Date(date);
        if (isNaN(d.getTime())) {
            throw new Error('Fecha inválida');
        }
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    },

    renderTimeSlots(slots, selectedDate, serviceDurationMessage) {
        this.debug('Iniciando renderizado de slots', {
            slotsCount: slots.length,
            selectedDate
        });

        const container = document.getElementById('timeSlots');
        if (!container) {
            throw new Error('Contenedor no encontrado');
        }

        slots
            .sort(([, a], [, b]) => {
                const timeA = a.hora_inicio || a.inicio || '';
                const timeB = b.hora_inicio || b.inicio || '';
                return timeA.localeCompare(timeB);
            })
            .forEach(([lapso, info]) => {
                try {
                    const button = this.createTimeSlotButton(lapso, info, selectedDate);
                    container.appendChild(button);
                } catch (error) {
                    this.debug('Error creando botón', {
                        lapso,
                        info,
                        error: error.message
                    });
                }
            });

        if (serviceDurationMessage) {
            serviceDurationMessage.classList[
                this.servicioSeleccionadoDuracion > 30 ? 'remove' : 'add'
            ]('hidden');
        }
    },

    createTimeSlotButton(lapso, info, selectedDate) {
        const button = document.createElement('button');
        button.type = 'button';
        
        // Usar hora_inicio y hora_fin en lugar de inicio y fin
        const horaInicio = info.hora_inicio || info.inicio || '';
        const horaFin = info.hora_fin || info.fin || '';
        
        this.debug('Creando botón para slot', { lapso, horaInicio, horaFin, info });
        
        button.textContent = `${horaInicio} - ${horaFin}`;
        button.setAttribute('data-lapso', lapso);
        button.setAttribute('data-info', JSON.stringify({
            inicio: horaInicio,
            fin: horaFin,
            ocupado: info.ocupado
        }));

        let isDisabled = info.ocupado;

        // Verificar si el slot ya pasó (solo para el día actual)
        if (this.isCurrentDay(selectedDate)) {
            const currentTime = new Date();
            const [hours, minutes] = horaInicio.split(':');
            const slotTime = new Date(selectedDate);
            slotTime.setHours(parseInt(hours, 10), parseInt(minutes, 10), 0);

            if (slotTime < currentTime) {
                isDisabled = true;
            }
        }

        button.className = `w-full p-2 rounded-md text-center transition-colors ${
            isDisabled 
                ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                : 'bg-white border border-gray-300 hover:bg-gray-50'
        }`;

        if (!isDisabled) {
            button.addEventListener('click', () => this.handleTimeSelection(lapso, info, button));
        }

        button.disabled = isDisabled;
        return button;
    },

    isCurrentDay(selectedDate) {
        const selected = new Date(selectedDate);
        const now = new Date();
        return selected.toDateString() === now.toDateString();
    },

    handleTimeSelection(lapso, timeInfo, button) {
        this.debug('Manejando selección de tiempo', { lapso, timeInfo });
        
        const fechaInput = document.getElementById('fecha_inicio');
        const datePicker = document.getElementById('fecha_selector');
        
        if (!datePicker?.value) {
            this.showError('Por favor, seleccione primero una fecha.');
            return;
        }

        if (this.servicioSeleccionadoDuracion <= 30) {
            this.handleSingleSelection(button, timeInfo, datePicker.value);
        } else {
            this.handleDoubleSelection(lapso, button);
        }
    },

    handleSingleSelection(button, timeInfo, selectedDate) {
        this.debug('Manejando selección simple', { timeInfo, selectedDate });
        
        document.querySelectorAll('#timeSlots button').forEach(btn => {
            btn.classList.remove('bg-red-600', 'text-white');
        });
        
        button.classList.add('bg-red-600', 'text-white');
        const fechaInput = document.getElementById('fecha_inicio');
        if (fechaInput) {
            const horaInicio = timeInfo.hora_inicio || timeInfo.inicio;
            fechaInput.value = `${selectedDate}T${horaInicio}`;
        }
    },

    handleDoubleSelection(lapso, selectedButton) {
        this.debug('Manejando selección doble', { lapso });
        
        const buttons = document.querySelectorAll('#timeSlots button');
        
        if (!this.primerHorarioSeleccionado) {
            this.resetSlotStyles();
            this.primerHorarioSeleccionado = lapso;
            selectedButton.classList.add('bg-red-600', 'text-white');
            
            const currentIndex = Array.from(buttons).indexOf(selectedButton);
            this.highlightAdjacentSlots(buttons, currentIndex);
        } else {
            this.processSecondSelection(buttons, selectedButton);
        }
    },

    resetSlotStyles() {
        document.querySelectorAll('#timeSlots button').forEach(btn => {
            btn.classList.remove('bg-red-600', 'text-white', 'bg-yellow-200');
        });
    },

    highlightAdjacentSlots(buttons, currentIndex) {
        if (currentIndex > 0 && !buttons[currentIndex - 1].disabled) {
            buttons[currentIndex - 1].classList.add('bg-yellow-200');
        }
        if (currentIndex < buttons.length - 1 && !buttons[currentIndex + 1].disabled) {
            buttons[currentIndex + 1].classList.add('bg-yellow-200');
        }
    },

    processSecondSelection(buttons, selectedButton) {
        const firstButton = Array.from(buttons).find(btn => 
            btn.getAttribute('data-lapso') === this.primerHorarioSeleccionado);
        const firstIndex = Array.from(buttons).indexOf(firstButton);
        const secondIndex = Array.from(buttons).indexOf(selectedButton);

        if (Math.abs(firstIndex - secondIndex) === 1) {
            this.confirmDoubleSelection(buttons, firstIndex, secondIndex);
        } else {
            this.showError('Por favor, seleccione dos horarios consecutivos');
        }
    },

    confirmDoubleSelection(buttons, firstIndex, secondIndex) {
        this.resetSlotStyles();
        
        const startIndex = Math.min(firstIndex, secondIndex);
        const endIndex = Math.max(firstIndex, secondIndex);
        
        buttons[startIndex].classList.add('bg-red-600', 'text-white');
        buttons[endIndex].classList.add('bg-red-600', 'text-white');

        const startButton = buttons[startIndex];
        const startInfo = JSON.parse(startButton.getAttribute('data-info') || '{}');
        const fechaSelector = document.getElementById('fecha_selector');

        if (fechaSelector?.value && startInfo.inicio) {
            const fecha = new Date(fechaSelector.value);
            const fechaInicio = document.getElementById('fecha_inicio');
            if (fechaInicio) {
                fechaInicio.value = `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}T${startInfo.inicio}`;
            }
        }

        this.primerHorarioSeleccionado = null;
    },

    showError(message) {
        this.debug('Mostrando error', { message });
        const errorContainer = document.getElementById('error-container');
        if (!errorContainer) return;

        errorContainer.classList.remove('hidden');
        const errorList = document.getElementById('error-list');
        
        if (errorList) {
            errorList.innerHTML = `<li>${message}</li>`;
        } else {
            errorContainer.textContent = message;
        }
        
        setTimeout(() => errorContainer.classList.add('hidden'), 5000);
    }
};

window.TimeSlotHandler = TimeSlotHandler;