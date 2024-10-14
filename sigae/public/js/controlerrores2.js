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
        // Primero, intentamos parsear como JSON
        return response.json().catch(() => {
            // Si falla, asumimos que es una redirección
            return response.text().then(text => {
                // Si el texto contiene una URL, consideramos que es una redirección
                if (text.includes('http') || text.startsWith('/')) {
                    window.location.href = text.trim();
                    throw new Error('Redirecting');
                }
                // Si no es una redirección, mostramos el texto como un error
                throw new Error(text);
            });
        });
    })
    .then(data => {
        if (!data.success) {
            displayErrors(data.errors || ['Ocurrió un error desconocido.']);
            if (data.debug) {
                console.log('Debug info:', data.debug);
            }
        } else {
            // Redirigir si la respuesta es exitosa
            window.location.href = data.redirect || '/home';
        }
    })
    .catch(error => {
        if (error.message !== 'Redirecting') {
            console.error('Error:', error);
            displayErrors([error.message || 'Ocurrió un error al procesar la solicitud. Por favor, inténtelo de nuevo.']);
        }
    });
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