const TimeSlotHandler = {
    servicioSeleccionadoDuracion: 0,
    primerHorarioSeleccionado: null,

    /**
     * Actualiza los slots de tiempo basado en la fecha seleccionada
     * @param {string} selectedDate - La fecha seleccionada
     */
    updateTimeSlots(selectedDate) {
        const timeSlotsContainer = document.getElementById('timeSlots');
        const serviceDurationMessage = document.getElementById('serviceDurationMessage');
        const loadingIndicator = document.getElementById('loadingIndicator');

        if (!timeSlotsContainer || !this.servicioSeleccionadoDuracion) {
            this.showError('Por favor, seleccione un servicio antes de elegir el horario.');
            return Promise.reject();
        }

        // Mostrar indicador de carga
        if (loadingIndicator) loadingIndicator.classList.remove('hidden');

        // Resetear selecciones previas
        this.primerHorarioSeleccionado = null;
        document.getElementById('fecha_inicio').value = '';
        timeSlotsContainer.innerHTML = '';

        // Ocultar mensajes de error previos
        const errorContainer = document.getElementById('error-container');
        if (errorContainer) errorContainer.classList.add('hidden');

        return this.fetchTimeSlots(selectedDate)
            .then(timeSlots => {
                if (!timeSlots || !timeSlots.horariosTaller || Object.keys(timeSlots.horariosTaller).length === 0) {
                    throw new Error('No hay horarios disponibles');
                }

                const sortedSlots = Object.entries(timeSlots.horariosTaller)
                    .sort((a, b) => a[1].inicio.localeCompare(b[1].inicio));

                // Verificar si es hoy
                const today = new Date();
                const selectedDateObj = new Date(selectedDate);
                const isToday = selectedDateObj.toDateString() === today.toDateString();
                const currentTime = new Date(timeSlots.horaActual || Date.now());

                sortedSlots.forEach(([lapso, info]) => {
                    const button = this.createTimeSlotButton(lapso, info, selectedDate, isToday, currentTime);
                    timeSlotsContainer.appendChild(button);
                });

                // Mostrar mensaje de duración si es necesario
                if (this.servicioSeleccionadoDuracion > 30 && serviceDurationMessage) {
                    serviceDurationMessage.classList.remove('hidden');
                } else if (serviceDurationMessage) {
                    serviceDurationMessage.classList.add('hidden');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showError(error.message);
                timeSlotsContainer.innerHTML = '';
            })
            .finally(() => {
                if (loadingIndicator) loadingIndicator.classList.add('hidden');
            });
    },

    /**
     * Obtiene los slots de tiempo del servidor
     */
    fetchTimeSlots(selectedDate) {
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
                return response.json().then(err => {
                    throw new Error(err.error || 'Error al obtener los horarios');
                });
            }
            return response.json();
        });
    },

    /**
     * Formatea la fecha para PHP
     */
    formatDateForPHP(date) {
        const d = new Date(date);
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    },

    /**
     * Crea un botón de slot de tiempo
     */
    createTimeSlotButton(lapso, info, selectedDate, isToday, currentTime) {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = `${info.inicio} - ${info.fin}`;
        button.setAttribute('data-lapso', lapso);
        button.setAttribute('data-info', JSON.stringify(info));

        // Determinar si el slot está deshabilitado
        let isDisabled = info.ocupado;
        
        if (isToday) {
            const [hours, minutes] = info.inicio.split(':').map(Number);
            const slotTime = new Date(selectedDate);
            slotTime.setHours(hours, minutes, 0, 0);
            
            if (slotTime < currentTime) {
                isDisabled = true;
            }
        }

        // Aplicar clases según el estado
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
        
        if (!datePicker.value) {
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
        const formattedDate = `${selectedDate}T${timeInfo.inicio}`;
        document.getElementById('fecha_inicio').value = formattedDate;
    },

    /**
     * Maneja la selección de slots dobles
     */
    handleDoubleSelection(lapso, selectedButton) {
        const buttons = document.querySelectorAll('#timeSlots button');
        
        if (!this.primerHorarioSeleccionado) {
            // Primera selección
            document.querySelectorAll('#timeSlots button').forEach(btn => {
                btn.classList.remove('bg-red-600', 'text-white', 'bg-yellow-200');
            });

            this.primerHorarioSeleccionado = lapso;
            selectedButton.classList.add('bg-red-600', 'text-white');

            // Resaltar slots adyacentes disponibles
            const currentIndex = Array.from(buttons).indexOf(selectedButton);
            this.highlightAdjacentSlots(buttons, currentIndex);
        } else {
            // Segunda selección
            this.confirmDoubleSelection(buttons, selectedButton);
        }
    },

    /**
     * Resalta los slots adyacentes disponibles
     */
    highlightAdjacentSlots(buttons, currentIndex) {
        if (currentIndex > 0 && !buttons[currentIndex - 1].disabled) {
            buttons[currentIndex - 1].classList.add('bg-yellow-200');
        }
        if (currentIndex < buttons.length - 1 && !buttons[currentIndex + 1].disabled) {
            buttons[currentIndex + 1].classList.add('bg-yellow-200');
        }
    },

    /**
     * Confirma la selección de slots dobles
     */
    confirmDoubleSelection(buttons, secondButton) {
        const firstButton = Array.from(buttons).find(btn => 
            btn.getAttribute('data-lapso') === this.primerHorarioSeleccionado);
        const firstIndex = Array.from(buttons).indexOf(firstButton);
        const secondIndex = Array.from(buttons).indexOf(secondButton);

        if (Math.abs(firstIndex - secondIndex) === 1) {
            document.querySelectorAll('#timeSlots button').forEach(btn => {
                btn.classList.remove('bg-red-600', 'text-white', 'bg-yellow-200');
            });

            const startButton = buttons[Math.min(firstIndex, secondIndex)];
            buttons[Math.min(firstIndex, secondIndex)].classList.add('bg-red-600', 'text-white');
            buttons[Math.max(firstIndex, secondIndex)].classList.add('bg-red-600', 'text-white');

            const startInfo = JSON.parse(startButton.getAttribute('data-info'));
            const fecha = new Date(document.getElementById('fecha_selector').value);
            const formattedDate = `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}T${startInfo.inicio}`;
            document.getElementById('fecha_inicio').value = formattedDate;
            this.primerHorarioSeleccionado = null;
        } else {
            this.showError('Por favor, seleccione dos horarios consecutivos');
            return;
        }
    },

    /**
     * Muestra mensajes de error
     */
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

// Hacer el handler disponible globalmente
window.TimeSlotHandler = TimeSlotHandler;