const form = document.getElementById('form');
const email = document.getElementById('email');
const password = document.getElementById('contrasena');

form.addEventListener('submit', (e) => {

    e.preventDefault(); 

    validateInputs();
});

const setSuccess = (element) => {
    const inputControl = elemnt.parentElemnt;
    const errorDisplay = inputControl.querySelector('.error');

    errorDisplay.innerText = '';
    inputControl.classList.remove('error');
    inputControl.classList.add('success');

};

const setError = (elemnt, message) => {
    const inputControl = element.parentElement;
    const errorDisplay = inputControl.querySelector('.error');
    errorDisplay.innerText = message;
    inputControl.classList.remove('success');
    inputControl.classList.add('error');

};

const validateEmail = (email) => {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
};

const validateInputs = () => {

    const emailValue = email.value.trim();
    const passwordValue = password.value.trim();

    if (emailValue === '') {
        setError(email, 'Por favor ingrese su correo electrónico');

    } else if (!isVaildEmail(emailValue)) {
        setError(email, 'Correo electrónico inválido');
    }   else {
        setSuccess(email);
    }

        if (passwordValue === '') {
            setError(password, 'Por favor ingrese una contraseña');

        } else if (passwordValue.lenght < 8) {
            setError(password, 'Contraseña debe tener al menos 8 caracteres');
        } else {
            setSuccess(password);
        }

};