const TimeSlotHandler = {
    servicioSeleccionadoDuracion: 0,
    primerHorarioSeleccionado: null,

    async fetchTimeSlots(selectedDate) {
        const loadingIndicator = document.getElementById('loadingIndicator');
        const timeSlotsContainer = document.getElementById('timeSlots');
        
        if (loadingIndicator) loadingIndicator.classList.remove('hidden');
        if (timeSlotsContainer) timeSlotsContainer.classList.add('hidden');
    
        try {
            console.log('Fetching time slots for date:', selectedDate);
            console.log('Request URL:', `${GET_BLOCKED_TIMES_URL}?date=${selectedDate}`);
            
            const response = await fetch(`${GET_BLOCKED_TIMES_URL}?date=${selectedDate}`);
            console.log('Response status:', response.status);
            console.log('Response headers:', [...response.headers.entries()]);

            // Si no es OK, obtener el texto de la respuesta para diagnóstico
            if (!response.ok) {
                const textResponse = await response.text();
                console.error('Error response text:', textResponse);
                throw new Error(`HTTP error! status: ${response.status}, body: ${textResponse.substring(0, 200)}...`);
            }

            // Intentar parsear como JSON solo si la respuesta es OK
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.error('Invalid content type:', contentType);
                const textResponse = await response.text();
                console.error('Non-JSON response:', textResponse.substring(0, 200));
                throw new Error('La respuesta del servidor no es JSON válido');
            }

            const data = await response.json();
            console.log('Parsed response data:', data);

            if (!data.success) {
                throw new Error(data.error || 'Error al obtener los horarios');
            }
            
            return data.horariosTaller || {};
        } catch (error) {
            console.error('Error completo:', error);
            // Agregar más contexto al error
            if (error.name === 'SyntaxError') {
                console.error('Error de parsing JSON - probablemente respuesta HTML en lugar de JSON');
            }
            throw error;
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
            fechaInput.value = datePicker.value;
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

                    buttons[Math.min(firstIndex, secondIndex)].classList.add('bg-red-600', 'text-white');
                    buttons[Math.max(firstIndex, secondIndex)].classList.add('bg-red-600', 'text-white');
                    
                    fechaInput.value = datePicker.value;
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
            const timeSlots = await this.fetchTimeSlots(selectedDate);
            console.log('Time slots received:', timeSlots);
            
            if (!timeSlots || Object.keys(timeSlots).length === 0) {
                this.showError('No hay horarios disponibles para la fecha seleccionada.');
                return;
            }
            
            // Convertir el objeto de lapsos a un array ordenado
            const sortedSlots = Object.entries(timeSlots).sort((a, b) => {
                return a[1].inicio.localeCompare(b[1].inicio);
            });
            
            console.log('Sorted slots:', sortedSlots);

            sortedSlots.forEach(([lapso, info]) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = `${info.inicio} - ${info.fin}`;
                button.setAttribute('data-lapso', lapso);
                
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
        } catch (error) {
            console.error('Error detallado en updateTimeSlots:', error);
            let errorMessage = 'Error al cargar los horarios. ';
            
            if (error.message.includes('500')) {
                errorMessage += 'Error interno del servidor. Por favor, contacte al administrador.';
            } else if (error.message.includes('Invalid JSON')) {
                errorMessage += 'Respuesta inválida del servidor.';
            } else {
                errorMessage += 'Por favor, intente nuevamente.';
            }
            
            this.showError(errorMessage);
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
            console.error('Error mostrado:', message);
            setTimeout(() => errorContainer.classList.add('hidden'), 5000);
        }
    }
};

window.TimeSlotHandler = TimeSlotHandler;
