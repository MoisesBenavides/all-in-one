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

        // Mostrar indicador de carga
        if (loadingIndicator) {
            loadingIndicator.classList.remove('hidden');
        }

        // Resetear estado
        this.primerHorarioSeleccionado = null;
        document.getElementById('fecha_inicio').value = '';
        timeSlotsContainer.innerHTML = '';

        return new Promise((resolve, reject) => {
            this.fetchTimeSlots(selectedDate)
                .then(response => {
                    if (!response || !response.success || !response.horariosTaller) {
                        throw new Error('Formato de respuesta inválido');
                    }

                    const slots = response.horariosTaller;
                    
                    // Validar y procesar los slots
                    const validSlots = Object.entries(slots)
                        .filter(([_, info]) => {
                            return info && 
                                   typeof info.inicio === 'string' && 
                                   typeof info.fin === 'string' &&
                                   typeof info.ocupado !== 'undefined';
                        })
                        .sort((a, b) => a[1].inicio.localeCompare(b[1].inicio));

                    if (validSlots.length === 0) {
                        throw new Error('No hay horarios disponibles para esta fecha');
                    }

                    // Limpiar contenedor
                    timeSlotsContainer.innerHTML = '';

                    // Renderizar slots válidos
                    validSlots.forEach(([lapso, info]) => {
                        const button = this.createTimeSlotButton(lapso, info, selectedDate);
                        timeSlotsContainer.appendChild(button);
                    });

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
                    this.showError('Error al cargar los horarios. Por favor, intente nuevamente.');
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

    fetchTimeSlots(selectedDate) {
        if (!selectedDate) {
            return Promise.reject(new Error('Fecha no seleccionada'));
        }

        const formattedDate = this.formatDateForPHP(selectedDate);
        const url = `${GET_BLOCKED_TIMES_URL}?date=${encodeURIComponent(formattedDate)}`;

        return fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include',
            mode: 'cors'
        })
        .then(async response => {
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || 'Error al obtener los horarios');
            }
            return response.json();
        })
        .then(data => {
            if (!data || !data.horariosTaller) {
                throw new Error('Respuesta inválida del servidor');
            }
            return data;
        });
    },

    createTimeSlotButton(lapso, info, selectedDate) {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = `${info.inicio} - ${info.fin}`;
        button.setAttribute('data-lapso', lapso);
        button.setAttribute('data-info', JSON.stringify(info));

        let isDisabled = info.ocupado;

        // Verificar si el horario ya pasó
        const now = new Date();
        const selectedDateObj = new Date(selectedDate);
        const isToday = selectedDateObj.toDateString() === now.toDateString();

        if (isToday) {
            const [hours, minutes] = info.inicio.split(':').map(Number);
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
            fechaInput.value = `${selectedDate}T${timeInfo.inicio}`;
        }
    },

    handleDoubleSelection(lapso, selectedButton) {
        const buttons = document.querySelectorAll('#timeSlots button');
        
        if (!this.primerHorarioSeleccionado) {
            this.resetSlotStyles();
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
        this.resetSlotStyles();
        
        const startIndex = Math.min(firstIndex, secondIndex);
        const endIndex = Math.max(firstIndex, secondIndex);
        
        buttons[startIndex].classList.add('bg-red-600', 'text-white');
        buttons[endIndex].classList.add('bg-red-600', 'text-white');

        const startButton = buttons[startIndex];
        const startInfo = JSON.parse(startButton.getAttribute('data-info') || '{}');
        
        if (startInfo.inicio) {
            const fechaSelector = document.getElementById('fecha_selector');
            if (fechaSelector?.value) {
                const fecha = new Date(fechaSelector.value);
                document.getElementById('fecha_inicio').value = 
                    `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}T${startInfo.inicio}`;
            }
        }

        this.primerHorarioSeleccionado = null;
    },

    resetSlotStyles() {
        document.querySelectorAll('#timeSlots button').forEach(btn => {
            btn.classList.remove('bg-red-600', 'text-white', 'bg-yellow-200');
        });
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