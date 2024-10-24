const ServiceHandler = {
    jsonUrl: null,

    setJsonUrl(url) {
        this.jsonUrl = url;
    },

    async cargarServicios() {
        try {
            if (!this.jsonUrl) {
                throw new Error('URL del JSON no configurada');
            }
            const response = await fetch(this.jsonUrl);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('Error al cargar los servicios:', error);
            throw error;
        }
    },

    setupDropdown(buttonId, menuId, optionsSelector, selectedOptionId, hiddenInputId) {
        const button = document.getElementById(buttonId);
        const menu = document.getElementById(menuId);
        const selectedOption = document.getElementById(selectedOptionId);
        const hiddenInput = document.getElementById(hiddenInputId);

        button.addEventListener('click', () => {
            menu.classList.toggle('hidden');
        });

        document.querySelectorAll(optionsSelector).forEach(option => {
            option.addEventListener('click', () => {
                selectedOption.textContent = option.textContent;
                hiddenInput.value = option.dataset.value;
                menu.classList.add('hidden');
            });
        });
    },

    updateServiceSubtypes(serviceType, servicios, onSelect) {
        const subtypes = {
            alineacion: ['A01', 'A02', 'A03'],
            balanceo: ['B01', 'B02', 'B03', 'B04'],
            neumaticos: ['N01', 'N02', 'N03', 'N04'],
            diagnostico: ['D01'],
            completo: ['SVC']
        };

        const tipoServicioOptions = document.getElementById('tipoServicioOptions');
        tipoServicioOptions.innerHTML = '';
        
        subtypes[serviceType].forEach(subtype => {
            const div = document.createElement('div');
            div.className = 'option cursor-pointer px-[22px] py-3.5 hover:bg-gray-100';
            div.textContent = servicios[subtype].descripcion;
            div.dataset.value = subtype;
            div.dataset.duracion = servicios[subtype].tiempo_estimado;
            div.addEventListener('click', () => onSelect(subtype, servicios[subtype]));
            tipoServicioOptions.appendChild(div);
        });
    }
};

window.ServiceHandler = ServiceHandler;