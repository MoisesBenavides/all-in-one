const nodemailer = require('nodemailer');
const correo = document.getElementById('email');

// Configurar el transporte SMTP
let transporter = nodemailer.createTransport({
    host: 'smtp-relay.brevo.com',  // Servidor SMTP de Brevo
    port: 587,  // Puerto SMTP de Brevo
    secure: false,  // true para puerto 465, false para otros puertos
    auth: {
        // Las credenciales se leen del entorno, nunca se escriben en el codigo.
        // Definir BREVO_SMTP_USER y BREVO_SMTP_KEY antes de ejecutar.
        user: process.env.BREVO_SMTP_USER,  // Login SMTP de Brevo
        pass: process.env.BREVO_SMTP_KEY,   // API Key de Brevo
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
