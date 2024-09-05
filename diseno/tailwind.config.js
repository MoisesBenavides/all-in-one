/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./dist/*.{html,js}"],
  theme: {
    extend: {
      colors: {
      'azul-clarito': '#243cff',
      'gris-oscuro': '#333333',
      'gris-claro': '#f8f9fa',
      'rojo': '#ff0000',
      'amarillo': '#ffff00',
      'verde': '#00ff00',//Aca hay colores como predeteminados que yo uso, poniendo el nombre de cualquiera de estos se pone ese color que esta en hex
      }
    },
  },
  plugins: [],
}

