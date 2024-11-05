const TimeSlotHandler = {
    servicioSeleccionadoDuracion: 0,
    primerHorarioSeleccionado: null,

    updateTimeSlots(selectedDate) {
        const timeSlotsContainer = document.getElementById('timeSlots');
        const serviceDurationMessage = document.getElementById('serviceDurationMessage');
        const loadingIndicator = document.getElementById('loadingIndicator');
        
        if (!timeSlotsContainer || !this.servicioSeleccionadoDuracion) {
            this.showError('Por favor, seleccione un servicio antes de elegir el horario.');
            return Promise.reject(new Error('Configuración incompleta'));
        }

        if (loadingIndicator) loadingIndicator.classList.remove('hidden');

        // Resetear estado
        this.primerHorarioSeleccionado = null;
        document.getElementById('fecha_inicio').value = '';
        timeSlotsContainer.innerHTML = '';

        return new Promise((resolve, reject) => {
            this.fetchTimeSlots(selectedDate)
                .then(response => {
                    if (!response.horariosTaller) {
                        throw new Error('No hay horarios disponibles');
                    }

                    const slots = response.horariosTaller;
                    const sortedSlots = Object.entries(slots)
                        .sort((a, b) => a[1].hora_inicio?.localeCompare(b[1].hora_inicio || ''));

                    sortedSlots.forEach(([lapso, info]) => {
                        const button = this.createTimeSlotButton(lapso, info, selectedDate);
                        timeSlotsContainer.appendChild(button);
                    });

                    if (serviceDurationMessage) {
                        serviceDurationMessage.classList[
                            this.servicioSeleccionadoDuracion > 30 ? 'remove' : 'add'
                        ]('hidden');
                    }

                    resolve(true);
                })
                .catch(error => {
                    console.error('Error en updateTimeSlots:', error);
                    this.showError('Error al cargar los horarios disponibles');
                    timeSlotsContainer.innerHTML = '';
                    reject(error);
                })
                .finally(() => {
                    if (loadingIndicator) loadingIndicator.classList.add('hidden');
                });
        });
    },

    fetchTimeSlots(selectedDate) {
        if (!selectedDate) return Promise.reject(new Error('Fecha no seleccionada'));

        const formattedDate = this.formatDateForPHP(selectedDate);
        const url = `${GET_BLOCKED_TIMES_URL}?date=${encodeURIComponent(formattedDate)}`;

        return fetch(url, {
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
                return response.json()
                    .then(data => {
                        throw new Error(data.error || 'Error al obtener los horarios');
                    })
                    .catch(() => {
                        throw new Error('Error al obtener los horarios');
                    });
            }
            return response.json();
        });
    },

    createTimeSlotButton(lapso, info, selectedDate) {
        const button = document.createElement('button');
        button.type = 'button';
        
        // Usar hora_inicio y hora_fin en lugar de inicio y fin
        const horaInicio = info.hora_inicio || info.inicio || '';
        const horaFin = info.hora_fin || info.fin || '';
        
        button.textContent = `${horaInicio} - ${horaFin}`;
        button.setAttribute('data-lapso', lapso);
        button.setAttribute('data-info', JSON.stringify({
            ...info,
            inicio: horaInicio, // Mantener compatibilidad con el resto del código
            fin: horaFin
        }));

        let isDisabled = info.ocupado;

        // Verificar si el horario ya pasó
        const now = new Date();
        const selectedDateObj = new Date(selectedDate);
        const isToday = selectedDateObj.toDateString() === now.toDateString();

        if (isToday && horaInicio) {
            const [hours, minutes] = horaInicio.split(':').map(Number);
            const slotTime = new Date(selectedDate);
            slotTime.setHours(hours, minutes, 0, 0);
            
            if (slotTime < now) {
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
            // Usar hora_inicio si está disponible, sino usar inicio
            const horaInicio = timeInfo.hora_inicio || timeInfo.inicio;
            fechaInput.value = `${selectedDate}T${horaInicio}`;
        }
    },

    handleDoubleSelection(lapso, selectedButton) {
        const buttons = document.querySelectorAll('#timeSlots button');
        
        if (!this.primerHorarioSeleccionado) {
            document.querySelectorAll('#timeSlots button').forEach(btn => {
                btn.classList.remove('bg-red-600', 'text-white', 'bg-yellow-200');
            });

            this.primerHorarioSeleccionado = lapso;
            selectedButton.classList.add('bg-red-600', 'text-white');

            const currentIndex = Array.from(buttons).indexOf(selectedButton);
            if (currentIndex > 0 && !buttons[currentIndex - 1].disabled) {
                buttons[currentIndex - 1].classList.add('bg-yellow-200');
            }
            if (currentIndex < buttons.length - 1 && !buttons[currentIndex + 1].disabled) {
                buttons[currentIndex + 1].classList.add('bg-yellow-200');
            }
        } else {
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

    confirmDoubleSelection(buttons, firstIndex, secondIndex) {
        document.querySelectorAll('#timeSlots button').forEach(btn => {
            btn.classList.remove('bg-red-600', 'text-white', 'bg-yellow-200');
        });

        const startIndex = Math.min(firstIndex, secondIndex);
        const endIndex = Math.max(firstIndex, secondIndex);

        buttons[startIndex].classList.add('bg-red-600', 'text-white');
        buttons[endIndex].classList.add('bg-red-600', 'text-white');

        const startButton = buttons[startIndex];
        const startInfo = JSON.parse(startButton.getAttribute('data-info') || '{}');
        const fechaSelector = document.getElementById('fecha_selector');

        if (fechaSelector?.value) {
            const fecha = new Date(fechaSelector.value);
            const horaInicio = startInfo.hora_inicio || startInfo.inicio;
            document.getElementById('fecha_inicio').value = 
                `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}T${horaInicio}`;
        }

        this.primerHorarioSeleccionado = null;
    },

    formatDateForPHP(date) {
        const d = new Date(date);
        if (isNaN(d.getTime())) {
            throw new Error('Fecha inválida');
        }
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
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
        
        setTimeout(() => errorContainer.classList.add('hidden'), 5000);
    }
};

window.TimeSlotHandler = TimeSlotHandler;