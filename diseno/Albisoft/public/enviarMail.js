const nodemailer = require('nodemailer');
const correo = document.getElementById('email');
const nombre = document.getElementById('nombre');
document.addEventListener('click', enviarCorreo);

// Crear un transportador reutilizable usando SMTP
let transporter = nodemailer.createTransport({
  host: "smtp.gmail.com",  // Ejemplo con Gmail
  port: 587,
  secure: false, // true para 465, false para otros puertos
  auth: {
    user: "Albisofttech@gmail.com", // tu dirección de correo
    pass: "albisoft1234" // tu contraseña
  }
});

// Función para enviar correo
async function enviarCorreo() {
  try {
    // Enviar correo con el objeto de transporte definido
    let info = await transporter.sendMail({
      from: '"Albisoft" <>', // dirección del remitente
      to: correo, // lista de destinatarios
      subject: "Gracias por elegirnos! ¿Como estas" + nombre , // Asunto
      text: "Cuentanos sobre que es tu proyecto", // cuerpo en texto plano
      html: "<b>Contenido del correo en HTML</b>" // cuerpo en html
    });

    console.log("Mensaje enviado: %s", info.messageId);
  } catch (error) {
    console.error("Error al enviar el correo:", error);
  }
}

// Llamar a la función
