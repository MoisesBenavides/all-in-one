const TimeSlotHandler = {
    servicioSeleccionadoDuracion: 0,
    primerHorarioSeleccionado: null,

    /**
     * Actualiza los slots de tiempo
     */
    updateTimeSlots(selectedDate) {
        // Validaciones iniciales
        const timeSlotsContainer = document.getElementById('timeSlots');
        const serviceDurationMessage = document.getElementById('serviceDurationMessage');
        const loadingIndicator = document.getElementById('loadingIndicator');
        
        if (!timeSlotsContainer) {
            console.error('No se encontró el contenedor de time slots');
            return Promise.reject(new Error('Error de configuración'));
        }

        if (!this.servicioSeleccionadoDuracion) {
            this.showError('Por favor, seleccione un servicio antes de elegir el horario.');
            return Promise.reject(new Error('Servicio no seleccionado'));
        }

        // Mostrar indicador de carga
        if (loadingIndicator) {
            loadingIndicator.classList.remove('hidden');
        }

        // Limpiar estado previo
        this.resetState(timeSlotsContainer);

        // Retornar una promesa
        return new Promise((resolve, reject) => {
            this.fetchTimeSlots(selectedDate)
                .then(response => {
                    if (!response || !response.success || !response.horariosTaller) {
                        throw new Error(response?.error || 'No hay horarios disponibles');
                    }

                    const slots = response.horariosTaller;
                    if (Object.keys(slots).length === 0) {
                        throw new Error('No hay horarios disponibles para esta fecha');
                    }

                    this.renderTimeSlots(slots, selectedDate, response.horaActual);

                    // Actualizar mensaje de duración
                    if (serviceDurationMessage) {
                        serviceDurationMessage.classList[
                            this.servicioSeleccionadoDuracion > 30 ? 'remove' : 'add'
                        ]('hidden');
                    }

                    resolve(true);
                })
                .catch(error => {
                    console.error('Error en updateTimeSlots:', error);
                    this.showError(error.message || 'Error al cargar los horarios');
                    timeSlotsContainer.innerHTML = '';
                    reject(error);
                })
                .finally(() => {
                    if (loadingIndicator) {
                        loadingIndicator.classList.add('hidden');
                    }
                });
        });
    },

    /**
     * Resetea el estado del componente
     */
    resetState(container) {
        this.primerHorarioSeleccionado = null;
        const fechaInicio = document.getElementById('fecha_inicio');
        if (fechaInicio) {
            fechaInicio.value = '';
        }
        container.innerHTML = '';
        
        const errorContainer = document.getElementById('error-container');
        if (errorContainer) {
            errorContainer.classList.add('hidden');
        }
    },

    /**
     * Obtiene los slots de tiempo del servidor
     */
    fetchTimeSlots(selectedDate) {
        if (!selectedDate) {
            return Promise.reject(new Error('Fecha no seleccionada'));
        }

        try {
            const formattedDate = this.formatDateForPHP(selectedDate);
            const url = `${GET_BLOCKED_TIMES_URL}?date=${encodeURIComponent(formattedDate)}`;

            return fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(response.status === 400 
                        ? 'Fecha inválida o fuera de rango'
                        : 'Error al obtener los horarios');
                }
                return response.json();
            });
        } catch (error) {
            return Promise.reject(error);
        }
    },

    /**
     * Renderiza los slots de tiempo
     */
    renderTimeSlots(slots, selectedDate, serverTime) {
        const container = document.getElementById('timeSlots');
        if (!container) return;

        const now = serverTime ? new Date(serverTime) : new Date();
        const selectedDateObj = new Date(selectedDate);
        const isToday = selectedDateObj.toDateString() === now.toDateString();

        Object.entries(slots)
            .sort((a, b) => a[1].inicio.localeCompare(b[1].inicio))
            .forEach(([lapso, info]) => {
                if (!this.isValidSlotInfo(info)) {
                    console.warn('Slot inválido:', lapso, info);
                    return;
                }

                try {
                    const button = this.createTimeSlotButton(lapso, info, selectedDate, isToday, now);
                    container.appendChild(button);
                } catch (error) {
                    console.warn('Error al crear botón:', error);
                }
            });
    },

    /**
     * Valida la información del slot
     */
    isValidSlotInfo(info) {
        return info && 
               typeof info.inicio === 'string' && 
               typeof info.fin === 'string' && 
               typeof info.ocupado !== 'undefined';
    },

    /**
     * Formatea la fecha para PHP
     */
    formatDateForPHP(date) {
        const d = new Date(date);
        if (isNaN(d.getTime())) {
            throw new Error('Fecha inválida');
        }
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    },

    /**
     * Crea un botón de time slot
     */
    createTimeSlotButton(lapso, info, selectedDate, isToday, currentTime) {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = `${info.inicio} - ${info.fin}`;
        button.setAttribute('data-lapso', lapso);
        button.setAttribute('data-info', JSON.stringify(info));

        let isDisabled = info.ocupado;

        if (isToday) {
            try {
                const [hours, minutes] = info.inicio.split(':').map(Number);
                const slotTime = new Date(selectedDate);
                slotTime.setHours(hours, minutes, 0, 0);
                
                if (slotTime < currentTime) {
                    isDisabled = true;
                }
            } catch (error) {
                console.warn('Error al procesar hora:', error);
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

    /**
     * Maneja la selección de tiempo
     */
    handleTimeSelection(lapso, timeInfo, button) {
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

    /**
     * Maneja la selección de un solo slot
     */
    handleSingleSelection(button, timeInfo, selectedDate) {
        document.querySelectorAll('#timeSlots button').forEach(btn => {
            btn.classList.remove('bg-red-600', 'text-white');
        });
        
        button.classList.add('bg-red-600', 'text-white');
        const fechaInput = document.getElementById('fecha_inicio');
        if (fechaInput) {
            fechaInput.value = `${selectedDate}T${timeInfo.inicio}`;
        }
    },

    /**
     * Maneja la selección de slots dobles
     */
    handleDoubleSelection(lapso, selectedButton) {
        const buttons = document.querySelectorAll('#timeSlots button');
        
        if (!this.primerHorarioSeleccionado) {
            // Primera selección
            this.resetSlotStyles();
            this.primerHorarioSeleccionado = lapso;
            selectedButton.classList.add('bg-red-600', 'text-white');
            
            // Resaltar slots adyacentes disponibles
            const currentIndex = Array.from(buttons).indexOf(selectedButton);
            if (currentIndex > 0 && !buttons[currentIndex - 1].disabled) {
                buttons[currentIndex - 1].classList.add('bg-yellow-200');
            }
            if (currentIndex < buttons.length - 1 && !buttons[currentIndex + 1].disabled) {
                buttons[currentIndex + 1].classList.add('bg-yellow-200');
            }
        } else {
            // Segunda selección
            const firstButton = Array.from(buttons).find(btn => 
                btn.getAttribute('data-lapso') === this.primerHorarioSeleccionado);
            const firstIndex = Array.from(buttons).indexOf(firstButton);
            const secondIndex = Array.from(buttons).indexOf(selectedButton);

            if (Math.abs(firstIndex - secondIndex) === 1) {
                this.confirmDoubleSelection(buttons, firstIndex, secondIndex);
            } else {
                this.showError('Por favor, seleccione dos horarios consecutivos');
            }
        }
    },

    /**
     * Confirma la selección de slots dobles
     */
    confirmDoubleSelection(buttons, firstIndex, secondIndex) {
        this.resetSlotStyles();
        
        const startIndex = Math.min(firstIndex, secondIndex);
        const endIndex = Math.max(firstIndex, secondIndex);
        
        buttons[startIndex].classList.add('bg-red-600', 'text-white');
        buttons[endIndex].classList.add('bg-red-600', 'text-white');

        const startButton = buttons[startIndex];
        const startInfo = JSON.parse(startButton.getAttribute('data-info') || '{}');
        const fechaSelector = document.getElementById('fecha_selector');
        const fechaInicio = document.getElementById('fecha_inicio');

        if (fechaSelector && fechaInicio && startInfo.inicio) {
            const fecha = new Date(fechaSelector.value);
            fechaInicio.value = `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}T${startInfo.inicio}`;
        }

        this.primerHorarioSeleccionado = null;
    },

    /**
     * Resetea los estilos de los slots
     */
    resetSlotStyles() {
        document.querySelectorAll('#timeSlots button').forEach(btn => {
            btn.classList.remove('bg-red-600', 'text-white', 'bg-yellow-200');
        });
    },

    /**
     * Muestra mensajes de error
     */
    showError(message) {
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

// Hacer el handler disponible globalmente
window.TimeSlotHandler = TimeSlotHandler;