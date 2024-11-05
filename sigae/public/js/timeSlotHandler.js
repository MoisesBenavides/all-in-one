const TimeSlotHandler = {
    servicioSeleccionadoDuracion: 0,
    primerHorarioSeleccionado: null,

    formatDateForPHP(date) {
        const d = new Date(date);
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    },

    fetchTimeSlots(selectedDate) {
        return new Promise((resolve, reject) => {
            const formattedDate = this.formatDateForPHP(selectedDate);
            const url = `${GET_BLOCKED_TIMES_URL}?date=${encodeURIComponent(formattedDate)}`;

            fetch(url, {
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
                        console.error('Error de servidor:', err);
                        throw new Error(err.error || 'Error al obtener los horarios');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (!data.horariosTaller || typeof data.horariosTaller !== 'object') {
                    throw new Error('Formato de respuesta inválido: falta "horariosTaller"');
                }

                const horariosProcesados = {};
                const now = new Date();
                const fechaSeleccionada = new Date(formattedDate);
                const esHoy = fechaSeleccionada.toDateString() === now.toDateString();

                // Procesar cada lapso y verificar la estructura
                Object.entries(data.horariosTaller).forEach(([lapso, info]) => {
                    // Validar que el lapso contenga 'inicio' y 'fin'
                    if (info && typeof info === 'object' && info.inicio && info.fin) {
                        let ocupado = Boolean(info.ocupado);

                        // Si es hoy, verificar si el lapso ya pasó
                        if (esHoy) {
                            const [horas, minutos] = info.inicio.split(':').map(Number);
                            const horaLapso = new Date(fechaSeleccionada);
                            horaLapso.setHours(horas, minutos, 0, 0);

                            if (horaLapso <= now) {
                                ocupado = true;
                            }
                        }

                        horariosProcesados[lapso] = {
                            inicio: info.inicio,
                            fin: info.fin,
                            ocupado: ocupado
                        };
                    } else {
                        console.warn(`Datos incompletos para el lapso: ${lapso}`, info);
                    }
                });

                resolve(horariosProcesados);
            })
            .catch(error => {
                console.error('Error en fetchTimeSlots:', error);
                reject(new Error('Error al obtener los horarios. Intente nuevamente o contacte al soporte.'));
            });
        });
    },

    updateTimeSlots(selectedDate) {
        const timeSlotsContainer = document.getElementById('timeSlots');
        const serviceDurationMessage = document.getElementById('serviceDurationMessage');
        const errorContainer = document.getElementById('error-container');

        if (!timeSlotsContainer || !this.servicioSeleccionadoDuracion) {
            this.showError('Por favor, seleccione un servicio antes de elegir el horario.');
            return Promise.reject();
        }

        const loadingIndicator = document.getElementById('loadingIndicator');
        if (loadingIndicator) loadingIndicator.classList.remove('hidden');

        this.primerHorarioSeleccionado = null;
        document.getElementById('fecha_inicio').value = '';
        timeSlotsContainer.innerHTML = '';
        
        if (errorContainer) errorContainer.classList.add('hidden');

        return this.fetchTimeSlots(selectedDate)
            .then(timeSlots => {
                if (!timeSlots || Object.keys(timeSlots).length === 0) {
                    throw new Error('No hay horarios disponibles');
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