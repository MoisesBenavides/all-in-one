const TimeSlotHandler = {
    serviceDuration: 0,
    firstSelectedSlot: null,

    /**
     * Initialize the time slot handler
     * @param {number} duration - Duration of the service in minutes
     */
    init(duration) {
        this.serviceDuration = duration;
        this.firstSelectedSlot = null;
        this.setupDateListener();
    },

    /**
     * Set up the date selector listener
     */
    setupDateListener() {
        const dateSelector = document.getElementById('fecha_selector');
        if (dateSelector) {
            dateSelector.addEventListener('change', () => this.loadTimeSlots(dateSelector.value));
        }
    },

    /**
     * Load time slots for a specific date
     * @param {string} selectedDate - The selected date in YYYY-MM-DD format
     */
    async loadTimeSlots(selectedDate) {
        if (!this.serviceDuration) {
            this.showError('Por favor, seleccione un servicio primero');
            return;
        }

        const container = document.getElementById('timeSlots');
        const loadingIndicator = document.getElementById('loadingIndicator');
        
        try {
            container.innerHTML = '';
            loadingIndicator.classList.remove('hidden');
            
            const response = await this.fetchTimeSlots(selectedDate);
            if (!response.success || !response.horariosTaller) {
                throw new Error('Error al cargar los horarios');
            }

            this.renderTimeSlots(response.horariosTaller, selectedDate, response.horaActual);
            
            if (this.serviceDuration > 30) {
                document.getElementById('serviceDurationMessage').classList.remove('hidden');
            } else {
                document.getElementById('serviceDurationMessage').classList.add('hidden');
            }
        } catch (error) {
            this.showError(error.message);
        } finally {
            loadingIndicator.classList.add('hidden');
        }
    },

    /**
     * Fetch time slots from the backend
     * @param {string} date - The selected date
     * @returns {Promise} Promise with the time slots data
     */
    async fetchTimeSlots(date) {
        const url = `${GET_BLOCKED_TIMES_URL}?date=${encodeURIComponent(date)}`;
        const response = await fetch(url, {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error('Error al obtener los horarios');
        }

        return response.json();
    },

    /**
     * Render time slots in the container
     * @param {Object} slots - Time slots data
     * @param {string} selectedDate - Selected date
     * @param {string} currentTime - Current time from server
     */
    renderTimeSlots(slots, selectedDate, currentTime) {
        const container = document.getElementById('timeSlots');
        const currentDateTime = new Date(currentTime);
        const selectedDateTime = new Date(selectedDate);
        const isToday = selectedDateTime.toDateString() === currentDateTime.toDateString();

        Object.entries(slots)
            .sort((a, b) => a[1].inicio.localeCompare(b[1].inicio))
            .forEach(([slotId, slotData]) => {
                const button = this.createTimeSlotButton(slotId, slotData, selectedDate, isToday, currentDateTime);
                container.appendChild(button);
            });
    },

    /**
     * Create a time slot button
     * @param {string} slotId - Slot identifier
     * @param {Object} slotData - Slot data
     * @param {string} selectedDate - Selected date
     * @param {boolean} isToday - Whether the selected date is today
     * @param {Date} currentTime - Current time
     * @returns {HTMLButtonElement} The created button
     */
    createTimeSlotButton(slotId, slotData, selectedDate, isToday, currentTime) {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = `${slotData.inicio} - ${slotData.fin}`;
        button.dataset.slotId = slotId;
        
        const [hours, minutes] = slotData.inicio.split(':');
        const slotTime = new Date(selectedDate);
        slotTime.setHours(parseInt(hours), parseInt(minutes));

        const isDisabled = slotData.ocupado || (isToday && slotTime < currentTime);
        
        button.className = `w-full p-2 rounded-md text-center transition-colors ${
            isDisabled 
                ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                : 'bg-white border border-gray-300 hover:bg-gray-50'
        }`;
        
        if (!isDisabled) {
            button.addEventListener('click', () => this.handleSlotSelection(button, slotData, selectedDate));
        }
        button.disabled = isDisabled;

        return button;
    },

    /**
     * Handle time slot selection
     * @param {HTMLButtonElement} button - Selected button
     * @param {Object} slotData - Slot data
     * @param {string} selectedDate - Selected date
     */
    handleSlotSelection(button, slotData, selectedDate) {
        if (this.serviceDuration <= 30) {
            this.handleSingleSlotSelection(button, slotData, selectedDate);
        } else {
            this.handleDoubleSlotSelection(button);
        }
    },

    /**
     * Handle single slot selection
     * @param {HTMLButtonElement} button - Selected button
     * @param {Object} slotData - Slot data
     * @param {string} selectedDate - Selected date
     */
    handleSingleSlotSelection(button, slotData, selectedDate) {
        document.querySelectorAll('#timeSlots button').forEach(btn => {
            btn.classList.remove('bg-red-600', 'text-white');
        });
        
        button.classList.add('bg-red-600', 'text-white');
        document.getElementById('fecha_inicio').value = 
            `${selectedDate}T${slotData.inicio}`;
    },

    /**
     * Handle double slot selection
     * @param {HTMLButtonElement} button - Selected button
     */
    handleDoubleSlotSelection(button) {
        const buttons = Array.from(document.querySelectorAll('#timeSlots button'));
        const currentIndex = buttons.indexOf(button);

        if (!this.firstSelectedSlot) {
            // First selection
            this.resetSlotSelections();
            this.firstSelectedSlot = currentIndex;
            button.classList.add('bg-red-600', 'text-white');
            
            // Highlight adjacent available slots
            this.highlightAdjacentSlots(currentIndex, buttons);
        } else {
            // Second selection
            if (Math.abs(this.firstSelectedSlot - currentIndex) === 1) {
                this.confirmDoubleSlotSelection(
                    Math.min(this.firstSelectedSlot, currentIndex),
                    Math.max(this.firstSelectedSlot, currentIndex),
                    buttons
                );
            } else {
                this.showError('Por favor, seleccione dos horarios consecutivos');
                return;
            }
        }
    },

    /**
     * Reset all slot selections
     */
    resetSlotSelections() {
        document.querySelectorAll('#timeSlots button').forEach(btn => {
            btn.classList.remove('bg-red-600', 'text-white', 'bg-yellow-200');
        });
    },

    /**
     * Highlight adjacent slots
     * @param {number} currentIndex - Current selected slot index
     * @param {Array} buttons - Array of slot buttons
     */
    highlightAdjacentSlots(currentIndex, buttons) {
        if (currentIndex > 0 && !buttons[currentIndex - 1].disabled) {
            buttons[currentIndex - 1].classList.add('bg-yellow-200');
        }
        if (currentIndex < buttons.length - 1 && !buttons[currentIndex + 1].disabled) {
            buttons[currentIndex + 1].classList.add('bg-yellow-200');
        }
    },

    /**
     * Confirm double slot selection
     * @param {number} startIndex - Start slot index
     * @param {number} endIndex - End slot index
     * @param {Array} buttons - Array of slot buttons
     */
    confirmDoubleSlotSelection(startIndex, endIndex, buttons) {
        this.resetSlotSelections();
        buttons[startIndex].classList.add('bg-red-600', 'text-white');
        buttons[endIndex].classList.add('bg-red-600', 'text-white');

        const startSlotData = JSON.parse(buttons[startIndex].dataset.slotData || '{}');
        const selectedDate = document.getElementById('fecha_selector').value;
        document.getElementById('fecha_inicio').value = `${selectedDate}T${startSlotData.inicio}`;
        
        this.firstSelectedSlot = null;
    },

    /**
     * Show error message
     * @param {string} message - Error message to display
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

// Make the handler available globally
window.TimeSlotHandler = TimeSlotHandler;