// LoginScript.js

console.log('Script de validación iniciado');

function initializeForm() {
  console.log('Inicializando formulario');

  const form = document.getElementById('login-form');
  const email = document.getElementById('email');
  const password = document.getElementById('contrasena');
  const errorContainer = document.getElementById('error-container');
  const errorList = document.getElementById('error-list');

  if (!form || !email || !password || !errorContainer || !errorList) {
    console.error('No se pudieron encontrar todos los elementos necesarios');
    return;
  }

  console.log('Todos los elementos del DOM encontrados');

  form.addEventListener('submit', (e) => {
    console.log('Formulario enviado');
    e.preventDefault();
    if (validateInputs()) {
      console.log('Validación exitosa, enviando formulario');
      submitForm();
    } else {
      console.log('Validación fallida');
    }
  });

  function setSuccess(element) {
    console.log('Setting success for', element.id);
    element.classList.remove('border-red-500');
    const errorDisplay = element.nextElementSibling;
    if (errorDisplay) {
      errorDisplay.textContent = '';
      errorDisplay.classList.add('hidden');
    }
  }

  function setError(element, message) {
    console.log('Setting error for', element.id, ':', message);
    element.classList.add('border-red-500');
    const errorDisplay = element.nextElementSibling;
    if (errorDisplay) {
      errorDisplay.textContent = message;
      errorDisplay.classList.remove('hidden');
    }
  }

  function isValidEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
  }

  function validateInputs() {
    console.log('Validando inputs');
    let isValid = true;
    const emailValue = email.value.trim();
    const passwordValue = password.value.trim();

    console.log('Email value:', emailValue);
    console.log('Password value:', passwordValue);

    if (emailValue === '') {
      console.log('Email is empty');
      setError(email, 'Por favor ingrese su correo electrónico');
      isValid = false;
    } else if (!isValidEmail(emailValue)) {
      console.log('Email is invalid');
      setError(email, 'Correo electrónico inválido');
      isValid = false;
    } else {
      console.log('Email is valid');
      setSuccess(email);
    }

    if (passwordValue === '') {
      console.log('Password is empty');
      setError(password, 'Por favor ingrese una contraseña');
      isValid = false;
    } else if (passwordValue.length < 8) {
      console.log('Password is too short');
      setError(password, 'La contraseña debe tener al menos 8 caracteres');
      isValid = false;
    } else {
      console.log('Password is valid');
      setSuccess(password);
    }

    console.log('Resultado de la validación:', isValid);
    return isValid;
  }

  function submitForm() {
    console.log('Enviando formulario al servidor');
    fetch(form.action, {
      method: 'POST',
      body: new FormData(form)
    })
    .then(response => {
      console.log('Respuesta recibida del servidor');
      return response.json();
    })
    .then(data => {
      console.log('Datos recibidos del servidor:', data);
      errorList.innerHTML = '';
      
      if (data.success) {
        console.log('Inicio de sesión exitoso, redirigiendo');
        window.location.href = 'index.php?action=home';
      } else {
        console.log('Error en el inicio de sesión, mostrando errores');
        errorContainer.classList.remove('hidden');
        data.errors.forEach(error => {
          const li = document.createElement('li');
          li.textContent = error;
          errorList.appendChild(li);
        });

        // Debug information
        console.log('Debug info:', data.debug);
        const debugInfo = document.createElement('pre');
        debugInfo.textContent = JSON.stringify(data.debug, null, 2);
        errorList.appendChild(debugInfo);
      }
    })
    .catch(error => {
      console.error('Error en la solicitud:', error);
    });
  }
}

// Intenta inicializar inmediatamente
initializeForm();
// Si falla, espera a que el DOM esté completamente cargado
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeForm);
} else {
  initializeForm();
}