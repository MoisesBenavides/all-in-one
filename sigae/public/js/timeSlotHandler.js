const TimeSlotHandler = {
    servicioSeleccionadoDuracion: 0,
    primerHorarioSeleccionado: null,

    
    async fetchTimeSlots(selectedDate) {
        const loadingIndicator = document.getElementById('loadingIndicator');
        const timeSlotsContainer = document.getElementById('timeSlots');
        
        try {
            if (loadingIndicator) loadingIndicator.classList.remove('hidden');
            if (timeSlotsContainer) timeSlotsContainer.classList.add('hidden');

            // Formatear la fecha como YYYY-MM-DD
            const fecha = new Date(selectedDate);
            const formattedDate = fecha.toLocaleDateString('en-CA');

            // Construir la URL de manera segura
            let url;
            if (GET_BLOCKED_TIMES_URL.includes('http')) {
                // Si es una URL absoluta
                url = `${GET_BLOCKED_TIMES_URL}${GET_BLOCKED_TIMES_URL.includes('?') ? '&' : '?'}date=${formattedDate}`;
            } else {
                // Si es una URL relativa
                const baseUrl = window.location.origin;
                url = `${baseUrl}${GET_BLOCKED_TIMES_URL}${GET_BLOCKED_TIMES_URL.includes('?') ? '&' : '?'}date=${formattedDate}`;
            }

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            });

            // Si hay error, capturar el mensaje específico
            if (!response.ok) {
                const errorText = await response.text();
                if (errorText.includes('Warning:')) {
                    throw new Error('Error al procesar la fecha seleccionada');
                }
                throw new Error(errorText || `Error del servidor: ${response.status}`);
            }

            const data = await response.json();

            if (!data.success || !data.horariosTaller) {
                throw new Error(data.error || 'No se pudieron obtener los horarios');
            }

            // Procesar y validar los horarios
            const horariosProcesados = {};
            Object.entries(data.horariosTaller).forEach(([lapso, info]) => {
                if (info && 'ocupado' in info && info.inicio && info.fin) {
                    horariosProcesados[lapso] = {
                        ocupado: Boolean(info.ocupado),
                        inicio: info.inicio.trim(),
                        fin: info.fin.trim()
                    };
                }
            });

            // Procesar horarios pasados si hay hora actual
            if (data.horaActual) {
                try {
                    const horaActual = new Date(data.horaActual);
                    const diaActual = new Date(formattedDate);

                    if (diaActual.toDateString() === horaActual.toDateString()) {
                        Object.entries(horariosProcesados).forEach(([lapso, info]) => {
                            const [horas, minutos] = info.inicio.split(':').map(Number);
                            const horaInicio = new Date(diaActual);
                            horaInicio.setHours(horas, minutos, 0, 0);

                            if (horaInicio <= horaActual) {
                                horariosProcesados[lapso].ocupado = true;
                            }
                        });
                    }
                } catch (e) {
                    console.warn('Error al procesar horarios pasados:', e);
                }
            }

            return horariosProcesados;

        } catch (error) {
            console.error('Error en fetchTimeSlots:', error);
            throw new Error(error.message || 'Error al obtener los horarios');
        } finally {
            if (loadingIndicator) loadingIndicator.classList.add('hidden');
            if (timeSlotsContainer) timeSlotsContainer.classList.remove('hidden');
        }
    },


    handleTimeSelection(lapso, timeInfo, button) {
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
            const fecha = new Date(datePicker.value);
            const formattedDate = `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}T${timeInfo.inicio}`;
            fechaInput.value = formattedDate;
            this.primerHorarioSeleccionado = null;
            
        } else {
            if (!this.primerHorarioSeleccionado) {
                document.querySelectorAll('#timeSlots button').forEach(btn => {
                    btn.classList.remove('bg-red-600', 'text-white', 'bg-yellow-200');
                });
                
                this.primerHorarioSeleccionado = lapso;
                button.classList.add('bg-red-600', 'text-white');
                
                const buttons = document.querySelectorAll('#timeSlots button');
                const currentIndex = Array.from(buttons).indexOf(button);
                
                if (currentIndex > 0 && !buttons[currentIndex - 1].disabled) {
                    buttons[currentIndex - 1].classList.add('bg-yellow-200');
                }
                if (currentIndex < buttons.length - 1 && !buttons[currentIndex + 1].disabled) {
                    buttons[currentIndex + 1].classList.add('bg-yellow-200');
                }
            } else {
                const buttons = document.querySelectorAll('#timeSlots button');
                const firstButton = Array.from(buttons).find(btn => 
                    btn.getAttribute('data-lapso') === this.primerHorarioSeleccionado);
                const firstIndex = Array.from(buttons).indexOf(firstButton);
                const secondIndex = Array.from(buttons).indexOf(button);
                
                if (Math.abs(firstIndex - secondIndex) === 1) {
                    document.querySelectorAll('#timeSlots button').forEach(btn => {
                        btn.classList.remove('bg-red-600', 'text-white', 'bg-yellow-200');
                    });

                    const startButton = buttons[Math.min(firstIndex, secondIndex)];
                    buttons[Math.min(firstIndex, secondIndex)].classList.add('bg-red-600', 'text-white');
                    buttons[Math.max(firstIndex, secondIndex)].classList.add('bg-red-600', 'text-white');
                    
                    const startInfo = JSON.parse(startButton.getAttribute('data-info'));
                    const fecha = new Date(datePicker.value);
                    const formattedDate = `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}T${startInfo.inicio}`;
                    fechaInput.value = formattedDate;
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
        const errorContainer = document.getElementById('error-container');

        if (errorContainer) errorContainer.classList.add('hidden');

        if (!timeSlotsContainer || !this.servicioSeleccionadoDuracion) {
            this.showError('Por favor, seleccione un servicio antes de elegir el horario.');
            return;
        }

        this.primerHorarioSeleccionado = null;
        document.getElementById('fecha_inicio').value = '';
        timeSlotsContainer.innerHTML = '';
    
        try {
            const timeSlots = await this.fetchTimeSlots(selectedDate);
            
            if (!timeSlots || Object.keys(timeSlots).length === 0) {
                this.showError('No hay horarios disponibles para la fecha seleccionada.');
                return;
            }
            
            const sortedSlots = Object.entries(timeSlots)
                .sort((a, b) => a[1].inicio.localeCompare(b[1].inicio));

            sortedSlots.forEach(([lapso, info]) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = `${info.inicio} - ${info.fin}`;
                button.setAttribute('data-lapso', lapso);
                button.setAttribute('data-info', JSON.stringify(info));
                
                const isDisabled = info.ocupado;
                
                button.className = `w-full p-2 rounded-md text-center transition-colors ${
                    isDisabled 
                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                        : 'bg-white border border-gray-300 hover:bg-gray-50'
                }`;
                
                if (!isDisabled) {
                    button.addEventListener('click', () => this.handleTimeSelection(lapso, info, button));
                }
                
                button.disabled = isDisabled;
                timeSlotsContainer.appendChild(button);
            });

            if (this.servicioSeleccionadoDuracion > 30 && serviceDurationMessage) {
                serviceDurationMessage.classList.remove('hidden');
            }
        } catch (error) {
            this.showError(error.message);
            timeSlotsContainer.innerHTML = '';
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