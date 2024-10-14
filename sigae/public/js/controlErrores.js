// Función para manejar el envío del formulario
// Function to handle form submission
function handleFormSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            displayErrors(data.errors || ['Ocurrió un error desconocido.']);
            if (data.debug) {
                console.log('Debug info:', data.debug);
            }
        } else {
            // Redirect on success
            window.location.href = data.redirect || '/home';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        displayErrors(['Ocurrió un error al procesar la solicitud. Por favor, inténtelo de nuevo.']);
    });
}



function handleFormSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (!data.success) {
                displayErrors(data.errors || ['Ocurrió un error desconocido.']);
                if (data.debug) {
                    console.log('Debug info:', data.debug);
                }
            } else {
                // Redirigir o manejar el éxito según sea necesario
                window.location.href = data.redirect || '/home';
            }
        } catch (e) {
            console.error('Error parsing JSON:', e);
            displayErrors(['Error al procesar la respuesta del servidor.']);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        displayErrors(['Ocurrió un error al procesar la solicitud. Por favor, inténtelo de nuevo.']);
    });
}

// Función para mostrar errores
function displayErrors(errors) {
    const errorContainer = document.getElementById('error-container');
    if (!errorContainer) {
        console.error('Error: No se encontró el contenedor de errores.');
        return;
    }
    errorContainer.innerHTML = '';
    errorContainer.style.display = 'block';

    errors.forEach(error => {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = error;
        errorContainer.appendChild(errorDiv);
    });
}

// Función para crear el contenedor de errores si no existe
function createErrorContainer() {
    if (!document.getElementById('error-container')) {
        const container = document.createElement('div');
        container.id = 'error-container';
        container.style.display = 'none';
        container.style.backgroundColor = '#ffcccc';
        container.style.color = '#ff0000';
        container.style.padding = '10px';
        container.style.marginBottom = '10px';
        document.body.insertBefore(container, document.body.firstChild);
    }
}



// Agregar el event listener al formulario y crear el contenedor de errores
document.addEventListener('DOMContentLoaded', () => {
    createErrorContainer();
    const form = document.querySelector('form'); // Asegúrate de que esto seleccione el formulario correcto
    if (form) {
        form.addEventListener('submit', handleFormSubmit);
    } else {
        console.error('No se encontró el formulario en la página.');
    }
});