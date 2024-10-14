// Función para manejar el envío del formulario
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('login-form');
    form.addEventListener('submit', handleFormSubmit);
});

function handleFormSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.indexOf("application/json") !== -1) {
            return response.json().then(data => ({type: 'json', data: data}));
        } else {
            return response.text().then(text => ({type: 'text', data: text}));
        }
    })
    .then(result => {
        if (result.type === 'json') {
            handleJsonResponse(result.data);
        } else {
            handleTextResponse(result.data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        displayErrors(['Ocurrió un error al procesar la solicitud. Por favor, inténtelo de nuevo.']);
    });
}

function handleJsonResponse(data) {
    if (!data.success) {
        displayErrors(data.errors || ['Ocurrió un error desconocido.']);
        if (data.debug) {
            console.log('Debug info:', data.debug);
        }
    } else {
        // Redirigir si la respuesta es exitosa
        window.location.href = data.redirect || '/home';
    }
}

function handleTextResponse(text) {
    // Si el texto contiene una URL, consideramos que es una redirección
    if (text.includes('http') || text.startsWith('/')) {
        window.location.href = text.trim();
    } else {
        // Si no es una redirección, mostramos el texto como un error
        displayErrors([text]);
    }
}

// Función para mostrar errores en el contenedor de errores
function displayErrors(errors) {
    const errorContainer = document.getElementById('error-container');
    errorContainer.innerHTML = '';  // Limpiar cualquier error previo
    errorContainer.classList.remove('hidden');
    errorContainer.classList.add('bg-red-100', 'text-red-700', 'p-4', 'rounded-lg', 'shadow-lg');

    errors.forEach(error => {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message mb-2';
        errorDiv.textContent = error;
        errorContainer.appendChild(errorDiv);
    });
}