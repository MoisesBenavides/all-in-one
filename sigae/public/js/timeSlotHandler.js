const TimeSlotHandler = {
    servicioSeleccionadoDuracion: 0,
    primerHorarioSeleccionado: null,

    updateTimeSlots(selectedDate) {
        const timeSlotsContainer = document.getElementById('timeSlots');
        const serviceDurationMessage = document.getElementById('serviceDurationMessage');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const errorContainer = document.getElementById('error-container');

        // Validaciones iniciales
        if (!timeSlotsContainer || !this.servicioSeleccionadoDuracion) {
            this.showError('Por favor, seleccione un servicio antes de elegir el horario.');
            return Promise.reject();
        }

        // Validar fecha
        if (!selectedDate) {
            this.showError('Por favor, seleccione una fecha válida.');
            return Promise.reject();
        }

        try {
            // Validar que la fecha sea válida
            const dateObj = new Date(selectedDate);
            if (isNaN(dateObj.getTime())) {
                this.showError('La fecha seleccionada no es válida.');
                return Promise.reject();
            }
        } catch (error) {
            this.showError('Error al procesar la fecha.');
            return Promise.reject();
        }

        // Mostrar loading y limpiar errores previos
        if (loadingIndicator) loadingIndicator.classList.remove('hidden');
        if (errorContainer) errorContainer.classList.add('hidden');

        // Limpiar estado previo
        this.primerHorarioSeleccionado = null;
        document.getElementById('fecha_inicio').value = '';
        timeSlotsContainer.innerHTML = '';

        // Hacer la petición al servidor
        return fetch(this.buildUrl(selectedDate), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include'
        })
        .then(response => {
            if (!response.ok) {
                if (response.status === 400) {
                    throw new Error('Fecha inválida o fuera de rango');
                }
                throw new Error('Error al obtener los horarios');
            }
            return response.json();
        })
        .then(data => {
            if (!data || !data.horariosTaller) {
                throw new Error('No se recibieron datos válidos del servidor');
            }

            const slots = Object.entries(data.horariosTaller)
                .sort((a, b) => {
                    const horaInicioA = a[1].hora_inicio || a[1].inicio || '';
                    const horaInicioB = b[1].hora_inicio || b[1].inicio || '';
                    return horaInicioA.localeCompare(horaInicioB);
                });

            slots.forEach(([lapso, info]) => {
                const button = this.createTimeSlotButton(lapso, info, selectedDate);
                timeSlotsContainer.appendChild(button);
            });

            if (serviceDurationMessage) {
                serviceDurationMessage.classList[
                    this.servicioSeleccionadoDuracion > 30 ? 'remove' : 'add'
                ]('hidden');
            }
        })
        .catch(error => {
            console.error('Error en updateTimeSlots:', error);
            this.showError(error.message || 'Error al cargar los horarios');
            timeSlotsContainer.innerHTML = '';
            throw error; // Re-lanzar el error para el manejo externo
        })
        .finally(() => {
            if (loadingIndicator) {
                loadingIndicator.classList.add('hidden');
            }
        });
    },

    buildUrl(selectedDate) {
        try {
            const date = new Date(selectedDate);
            // Asegurarse de que la fecha es válida
            if (isNaN(date.getTime())) {
                throw new Error('Fecha inválida');
            }
            
            // Formatear la fecha como YYYY-MM-DD
            const formattedDate = date.toISOString().split('T')[0];
            
            // Construir y retornar la URL
            return `${GET_BLOCKED_TIMES_URL}?date=${encodeURIComponent(formattedDate)}`;
        } catch (error) {
            console.error('Error al construir URL:', error);
            throw new Error('Error al procesar la fecha');
        }
    },

    createTimeSlotButton(lapso, info, selectedDate) {
        const button = document.createElement('button');
        button.type = 'button';
        
        // Usar los campos correctos del backend
        const horaInicio = info.hora_inicio || info.inicio || '';
        const horaFin = info.hora_fin || info.fin || '';
        
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
            document.getElementById('fecha_inicio').value = 
                `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}T${startInfo.inicio}`;
        }

        this.primerHorarioSeleccionado = null;
    },

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
        
        console.error('Error mostrado:', message);
        
        setTimeout(() => errorContainer.classList.add('hidden'), 5000);
    }
};

window.TimeSlotHandler = TimeSlotHandler;