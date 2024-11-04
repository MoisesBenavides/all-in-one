console.log("hola");
const TimeSlotHandler = {
    servicioSeleccionadoDuracion: 0,
    primerHorarioSeleccionado: null,

    async fetchTimeSlots(selectedDate) {
        const loadingIndicator = document.getElementById('loadingIndicator');
        const timeSlotsContainer = document.getElementById('timeSlots');
        
        if (loadingIndicator) loadingIndicator.classList.remove('hidden');
        if (timeSlotsContainer) timeSlotsContainer.classList.add('hidden');
    
        try {
            // Formatear fecha como YYYY-MM-DD para coincidir 
            const fecha = new Date(selectedDate);
            const formattedDate = fecha.getFullYear() + '-' + 
                String(fecha.getMonth() + 1).padStart(2, '0') + '-' + 
                String(fecha.getDate()).padStart(2, '0');
            
            const baseUrl = GET_BLOCKED_TIMES_URL;
            const url = `${baseUrl}?date=${encodeURIComponent(formattedDate)}`;
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error('Error al obtener los horarios');
            }

            const data = await response.json();

            if (!data.success || !data.horariosTaller) {
                throw new Error(data.error || 'No hay horarios disponibles');
            }

            // Validar y procesar horarios pasados si es el día actual
            if (data.horaActual) {
                const horaActual = new Date(data.horaActual);
                const fechaSeleccionada = new Date(selectedDate);

                // Solo procesar si es el día actual
                if (fechaSeleccionada.toDateString() === horaActual.toDateString()) {
                    Object.entries(data.horariosTaller).forEach(([lapso, horario]) => {
                        // Crear fecha completa para comparar
                        const horaInicioCompleta = new Date(
                            fechaSeleccionada.getFullYear(),
                            fechaSeleccionada.getMonth(),
                            fechaSeleccionada.getDate(),
                            ...horario.inicio.split(':').map(Number)
                        );

                        // Marcar como ocupado si la hora ya pasó
                        if (horaInicioCompleta < horaActual) {
                            data.horariosTaller[lapso].ocupado = true;
                        }
                    });
                }
            }

            return data.horariosTaller;
        } catch (error) {
            throw new Error(`Error al obtener horarios: ${error.message}`);
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
            // Limpiar selecciones previas
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
                // Primera selección para servicios de más de 30 minutos
                document.querySelectorAll('#timeSlots button').forEach(btn => {
                    btn.classList.remove('bg-red-600', 'text-white', 'bg-yellow-200');
                });
                
                this.primerHorarioSeleccionado = lapso;
                button.classList.add('bg-red-600', 'text-white');
                
                const buttons = document.querySelectorAll('#timeSlots button');
                const currentIndex = Array.from(buttons).indexOf(button);
                
                // Resaltar horarios adyacentes disponibles
                if (currentIndex > 0 && !buttons[currentIndex - 1].disabled) {
                    buttons[currentIndex - 1].classList.add('bg-yellow-200');
                }
                if (currentIndex < buttons.length - 1 && !buttons[currentIndex + 1].disabled) {
                    buttons[currentIndex + 1].classList.add('bg-yellow-200');
                }
            } else {
                // Segunda selección para servicios de más de 30 minutos
                const buttons = document.querySelectorAll('#timeSlots button');
                const firstButton = Array.from(buttons).find(btn => 
                    btn.getAttribute('data-lapso') === this.primerHorarioSeleccionado);
                const firstIndex = Array.from(buttons).indexOf(firstButton);
                const secondIndex = Array.from(buttons).indexOf(button);
                
                if (Math.abs(firstIndex - secondIndex) === 1) {
                    // Selección válida de horarios consecutivos
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
        const errorList = document.getElementById('error-list');

        // Limpiar errores previos
        if (errorContainer) {
            errorContainer.classList.add('hidden');
        }
        if (errorList) {
            errorList.innerHTML = '';
        }

        if (!timeSlotsContainer || !this.servicioSeleccionadoDuracion) {
            this.showError('Por favor, seleccione un servicio antes de elegir el horario.');
            return;
        }

        this.primerHorarioSeleccionado = null;
        document.getElementById('fecha_inicio').value = '';
        timeSlotsContainer.innerHTML = '';
    
        try {
            const timeSlots = await this.fetchTimeSlots(selectedDate);
            
            const sortedSlots = Object.entries(timeSlots)
                .sort((a, b) => a[1].inicio.localeCompare(b[1].inicio))
                .filter(([_, info]) => info && info.inicio && info.fin);

            if (sortedSlots.length === 0) {
                this.showError('No hay horarios disponibles para la fecha seleccionada.');
                return;
            }

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
            this.showError(error.message || 'Error al cargar los horarios');
        }
    },

    showError(message) {
        const errorContainer = document.getElementById('error-container');
        const errorList = document.getElementById('error-list');

        if (!errorContainer) return;

        errorContainer.classList.remove('hidden');
        
        if (errorList) {
            errorList.innerHTML = '';
            const li = document.createElement('li');
            li.textContent = message;
            errorList.appendChild(li);
        } else {
            errorContainer.textContent = message;
        }
        
        setTimeout(() => {
            errorContainer.classList.add('hidden');
        }, 5000);
    }
};

window.TimeSlotHandler = TimeSlotHandler;