const nodemailer = require('nodemailer');
const correo = document.getElementById('email');

// Configurar el transporte SMTP
let transporter = nodemailer.createTransport({
    host: 'smtp-relay.brevo.com',  // Servidor SMTP de Brevo
    port: 587,  // Puerto SMTP de Brevo
    secure: false,  // true para puerto 465, false para otros puertos
    auth: {
        user: '7d34b2001@smtp-brevo.com',  // Aquí va tu API Key de Brevo
        pass: 'p7SgRFachNk8K5nD',  // Usar la misma API Key como contraseña
    },
});

// Opciones del correo
let mailOptions = {
    from: '"Albisoft" <albisofttech@gmail.com>',  // Remitente del correo
    to: correo,  // Destinatarios del correo
    subject: 'Asunto del correo',  // Asunto del correo
    text: 'Este es un mensaje de prueba enviado usando SMTP con Brevo.',  // Texto plano
    html: '<b>Este es un mensaje de prueba enviado usando SMTP con Brevo.</b>',  // HTML del correo
};

// Enviar el correo
transporter.sendMail(mailOptions, (error, info) => {
    if (error) {
        return console.log('Error al enviar el correo: ', error);
    }
    console.log('Correo enviado: %s', info.messageId);
    console.log('URL de vista previa: %s', nodemailer.getTestMessageUrl(info));
});
