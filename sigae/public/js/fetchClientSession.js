function fetchSessionData() {
    fetch("{{ path('getClienSession') }}", {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin' // Importante para mantener la sesión
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        // Guarda los datos en variables globales
        window.sessionData = {
            ultima_solicitud: data.ultima_solicitud,
            id: data.id,
            ci: data.ci,
            email: data.email,
            nombre: data.nombre,
            apellido: data.apellido,
            telefono: data.telefono,
            fotoPerfil: data.fotoPerfil
        };
        
        const event = new CustomEvent('sessionDataLoaded', { detail: window.sessionData });
        document.dispatchEvent(event);
    })
    .catch(error => {
        console.error('Error al obtener los datos de sesión:', error);
    });
}

// Ejecutar cuando se carga la página
document.addEventListener('DOMContentLoaded', fetchSessionData);


// Ejemplo de cómo escuchar cuando los datos están listos
document.addEventListener('sessionDataLoaded', (event) => {
    const sessionData = event.detail;
    // Aquí puedes hacer lo que necesites con los datos
    console.log('Datos de sesión cargados:', sessionData);
});