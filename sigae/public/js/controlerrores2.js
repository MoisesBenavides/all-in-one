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
                    // Redirigir si la respuesta es exitosa
                    window.location.href = data.redirect || '/home';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                displayErrors(['Ocurrió un error al procesar la solicitud. Por favor, inténtelo de nuevo.']);
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